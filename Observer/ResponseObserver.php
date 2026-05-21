<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\App\State as AppState;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\Locale\ResolverInterface as LocaleResolver;
use Magento\Store\Model\StoreManagerInterface;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use BitBabit\DeveloperTools\Service\ComprehensiveProfilerService;
use BitBabit\DeveloperTools\Service\SsrLogStorageService;
use Magento\Framework\App\ResourceConnection;
use BitBabit\DeveloperTools\Service\ApiKeyCookieManagerService;
use BitBabit\DeveloperTools\Service\DebugLogger;
use Magento\Framework\HTTP\PhpEnvironment\Request;

/**
 * ResponseObserver
 * @package BitBabit\DeveloperTools\Observer
 */
class ResponseObserver implements ObserverInterface
{
    public function __construct(
        private ProfilerConfigInterface $config,
        private ComprehensiveProfilerService $comprehensiveProfiler,
        private AssetRepository $assetRepository,
        private AppState $appState,
        private DesignInterface $design,
        private LocaleResolver $localeResolver,
        private StoreManagerInterface $storeManager,
        private ResourceConnection $resourceConnection,
        private ApiKeyCookieManagerService $cookieManagerService,
        private Request $request,
        private SsrLogStorageService $ssrLogStorage,
        private DebugLogger $debugLogger
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->config->shouldProfileRequest($this->request)) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $profiler   = $connection->getProfiler();
        if (!$profiler->getEnabled()) {
            return;
        }

        /** @var Response $response */
        $response    = $observer->getData('response');
        $contentType = $this->getContentType();

        // Echo X-SSR-ID back on the response so the Chrome extension can correlate logs.
        $ssrId = $this->request->getHeader('X-SSR-ID');
        if ($ssrId && $this->ssrLogStorage->isValidSsrId((string) $ssrId)) {
            $response->setHeader('X-SSR-ID', $ssrId);
        }

        // Early exit if no valid content type or injection is disabled
        if (!$this->shouldInjectProfilerData($contentType)) {
            return;
        }

        $this->setProfilerCookies();

        $comprehensiveData = $this->comprehensiveProfiler->getComprehensiveData();

        // SSR: persist profiler data keyed by SSR ID so the Chrome extension can fetch it.
        $this->storeSsrProfilerData($comprehensiveData);

        if ($this->isJsonResponse($contentType)) {
            $response->setHeader('X-Debug-Mode', 'true');
            $this->injectJsonProfilerData($response, $comprehensiveData);
        } else {
            $this->injectHtmlProfilerData($response, $comprehensiveData);
        }
    }

    private function getContentType(): ?string
    {
        $contentTypeHeader = $this->comprehensiveProfiler->getHeader('Content-Type')
            ?? $this->comprehensiveProfiler->getHeader('Accept');
        if (!$contentTypeHeader) {
            return null;
        }
        return (string) $contentTypeHeader;
    }

    private function shouldInjectProfilerData(?string $contentType): bool
    {
        return ($this->isJsonResponse($contentType) && $this->config->isJsonInjectionEnabled())
            || ($this->isHtmlResponse($contentType) && $this->config->isHtmlOutputEnabled());
    }

    private function isJsonResponse(?string $contentType): bool
    {
        return $contentType !== null && (
            str_contains($contentType, 'application/json') ||
            str_contains($contentType, 'application/vnd.api+json')
        );
    }

    private function isHtmlResponse(?string $contentType): bool
    {
        return $contentType !== null && str_contains($contentType, 'text/html');
    }

    private function injectJsonProfilerData(Response $response, array $profilerData): void
    {
        $content = $response->getContent();
        $data    = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data['_profiler'] = $profilerData;
            $response->setContent(json_encode($data));
        }
    }

    private function injectHtmlProfilerData(Response $response, array $profilerData): void
    {
        $content = $response->getContent();
        if ($this->config->isToolbarWidgetEnabled()) {
            $profilerScript = $this->generateProfilerScript($profilerData);
            $content        = str_replace('</body>', $profilerScript . '</body>', $content);
            $response->setContent($content);
        }
    }

    private function generateProfilerScript(array $data): string
    {
        $profilerDataJson = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsUrl            = $this->getJavaScriptUrl();
        $cssUrl           = $this->getCssUrl();

        return <<<HTML
        <!-- Developer Tools Profiler -->
        <link rel="stylesheet" type="text/css" href="{$cssUrl}">
        <script>
            (function() {
                var script = document.createElement('script');
                script.src = '{$jsUrl}';
                script.onload = function() {
                    if (window.DevProfiler) {
                        window.DevProfiler.addInitialPageData({$profilerDataJson});
                    } else {
                        setTimeout(function() {
                            if (window.DevProfiler) {
                                window.DevProfiler.addInitialPageData({$profilerDataJson});
                            }
                        }, 100);
                    }
                };
                script.onerror = function() {
                    console.error('Failed to load Developer Tools profiler script');
                };
                document.head.appendChild(script);
            })();
        </script>
        HTML;
    }

    private function getJavaScriptUrl(): string
    {
        try {
            $params = [
                'area'   => $this->getCurrentArea(),
                'theme'  => $this->getCurrentTheme(),
                'locale' => $this->getCurrentLocale(),
                'module' => 'BitBabit_DeveloperTools'
            ];
            return $this->assetRepository->createAsset(
                'BitBabit_DeveloperTools::js/profiler-widget.js',
                $params
            )->getUrl();
        } catch (\Exception $e) {
            return '/app/code/BitBabit/DeveloperTools/view/frontend/web/js/profiler-widget.js';
        }
    }

    private function getCssUrl(): string
    {
        try {
            $params = [
                'area'   => $this->getCurrentArea(),
                'theme'  => $this->getCurrentTheme(),
                'locale' => $this->getCurrentLocale(),
                'module' => 'BitBabit_DeveloperTools'
            ];
            return $this->assetRepository->createAsset(
                'BitBabit_DeveloperTools::css/profiler-widget.css',
                $params
            )->getUrl();
        } catch (\Exception $e) {
            return '/app/code/BitBabit/DeveloperTools/view/frontend/web/css/profiler-widget.css';
        }
    }

    private function getCurrentArea(): string
    {
        try {
            return $this->appState->getAreaCode();
        } catch (\Exception $e) {
            return 'frontend';
        }
    }

    private function getCurrentTheme(): string
    {
        try {
            $themeCode = $this->design->getDesignTheme()->getCode();
            return $themeCode ? (string) $themeCode : 'Magento/luma';
        } catch (\Exception $e) {
            return 'Magento/luma';
        }
    }

    private function getCurrentLocale(): string
    {
        try {
            return $this->localeResolver->getLocale();
        } catch (\Exception $e) {
            return 'en_US';
        }
    }

    private function setProfilerCookies(): void
    {
        try {
            if (!$this->cookieManagerService->get()) {
                $this->cookieManagerService->set($this->config->getApiKey());
            }
        } catch (\Exception $e) {
            $this->debugLogger->error('Failed to set profiler cookies', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Persist profiler payload keyed by X-SSR-ID so the Chrome extension can retrieve it.
     */
    private function storeSsrProfilerData(array $profilerData): void
    {
        $ssrId = $this->request->getHeader('X-SSR-ID');
        if (!$ssrId || !$this->ssrLogStorage->isValidSsrId((string) $ssrId)) {
            return;
        }

        $this->ssrLogStorage->append((string) $ssrId, [
            'captured_at'  => gmdate('c'),
            'request'      => [
                'method' => $profilerData['request']['method'] ?? null,
                'uri'    => $profilerData['request']['uri'] ?? null,
            ],
            'profiler_data' => $profilerData,
        ]);
    }
}
