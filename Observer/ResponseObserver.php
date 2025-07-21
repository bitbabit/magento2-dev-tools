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
use Magento\Framework\App\ResourceConnection;
use BitBabit\DeveloperTools\Service\ApiKeyCookieManagerService;

/**
 * ResponseObserver
 * @package BitBabit\DeveloperTools\Observer
 */
class ResponseObserver implements ObserverInterface
{
    /**
     * ResponseObserver constructor
     * @param ProfilerConfigInterface $config
     * @param ComprehensiveProfilerService $comprehensiveProfiler
     * @param AssetRepository $assetRepository
     * @param AppState $appState
     * @param DesignInterface $design
     * @param LocaleResolver $localeResolver
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resourceConnection
     * @param ApiKeyCookieManagerService $cookieManagerService
     */
    public function __construct(
        private ProfilerConfigInterface $config,
        private ComprehensiveProfilerService $comprehensiveProfiler,
        private AssetRepository $assetRepository,
        private AppState $appState,
        private DesignInterface $design,
        private LocaleResolver $localeResolver,
        private StoreManagerInterface $storeManager,
        private ResourceConnection $resourceConnection,
        private ApiKeyCookieManagerService $cookieManagerService
    ) {
    }

    /**
     * execute
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        // In your module's observer or plugin        
        // Early exit if profiler is not available or enabled
        $connection = $this->resourceConnection->getConnection();
        $profiler = $connection->getProfiler();
        if (!$profiler->getEnabled()) {
            return;
        }        

        /** @var Response $response */
        $response = $observer->getData('response');
        $contentType = $this->getContentType();
        // Early exit if no valid content type or injection is disabled
        if (!$this->shouldInjectProfilerData($contentType)) {
            return;
        }
        
        $this->setProfilerCookies();

        $comprehensiveData = $this->comprehensiveProfiler->getComprehensiveData();

        if ($this->isJsonResponse($contentType)) {
            $response->setHeader('X-Debug-Mode', 'true');
            $this->injectJsonProfilerData($response, $comprehensiveData);
        } else {
            $this->injectHtmlProfilerData($response, $comprehensiveData);
        }
    }

    /**
     * Get content type from response headers
     * @return string|null
     */
    private function getContentType(): ?string
    {
        $contentTypeHeader = $this->comprehensiveProfiler->getHeader('Content-Type')
            ?? $this->comprehensiveProfiler->getHeader('Accept');
        if (!$contentTypeHeader) {
            return null;
        }
        
        return (string) $contentTypeHeader;
    }

    /**
     * Determine if profiler data should be injected based on content type and config
     * @param string|null $contentType
     * @return bool
     */
    private function shouldInjectProfilerData(?string $contentType): bool
    {
        return ($this->isJsonResponse($contentType) && $this->config->isJsonInjectionEnabled())
            || ($this->isHtmlResponse($contentType) && $this->config->isHtmlOutputEnabled());
    }

    /**
     * Check if response is JSON
     * @param string|null $contentType
     * @return bool
     */
    private function isJsonResponse(?string $contentType): bool
    {
        return $contentType !== null && (
            str_contains($contentType, 'application/json') ||
            str_contains($contentType, 'application/vnd.api+json')
        );
    }

    /**
     * Check if response is HTML
     * @param string|null $contentType
     * @return bool
     */
    private function isHtmlResponse(?string $contentType): bool
    {
        return $contentType !== null && str_contains($contentType, 'text/html');
    }

    /**
     * Inject profiler data into JSON response
     * @param Response $response
     * @param array $profilerData
     * @return void
     */
    private function injectJsonProfilerData(Response $response, array $profilerData): void
    {
        $content = $response->getContent();
        $data = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $data['_profiler'] = $profilerData;
            $response->setContent(json_encode($data));
        }
    }

    /**
     * Inject profiler data into HTML response
     * @param Response $response
     * @param array $profilerData
     * @return void
     */
    private function injectHtmlProfilerData(Response $response, array $profilerData): void
    {
        $content = $response->getContent();
        if ($this->config->isToolbarWidgetEnabled()) {
            $profilerScript = $this->generateProfilerScript($profilerData);
            $content = str_replace('</body>', $profilerScript . '</body>', $content);
            $response->setContent($content);
        }
    }

    /**
     * Generate profiler JavaScript injection script
     * @param array $data
     * @return string
     */
    private function generateProfilerScript(array $data): string
    {
        // Escape the profiler data for safe JavaScript injection
        $profilerDataJson = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        // Get the URL for the JavaScript file
        $jsUrl = $this->getJavaScriptUrl();
        $cssUrl = $this->getCssUrl();

        return <<<HTML
        <!-- Developer Tools Profiler -->
        <link rel="stylesheet" type="text/css" href="{$cssUrl}">
        <script>
            // Load the profiler widget script
            (function() {
                var script = document.createElement('script');
                script.src = '{$jsUrl}';
                script.onload = function() {
                    // Initialize with profiler data once the script is loaded
                    if (window.DevProfiler) {
                        window.DevProfiler.addInitialPageData({$profilerDataJson});
                    } else {
                        // Fallback: wait a bit and try again
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

    /**
     * Get JavaScript file URL with proper asset context
     * @return string
     */
    private function getJavaScriptUrl(): string
    {
        try {
            // Build proper parameters array - THIS IS THE KEY FIX
            $params = [
                'area' => $this->getCurrentArea(),
                'theme' => $this->getCurrentTheme(),
                'locale' => $this->getCurrentLocale(),
                'module' => 'BitBabit_DeveloperTools'
            ];

            // Use Magento's asset repository to get the proper URL for the JavaScript file
            $asset = $this->assetRepository->createAsset(
                'BitBabit_DeveloperTools::js/profiler-widget.js',
                $params
            );

            return $asset->getUrl();
        } catch (\Exception $e) {
            // Fallback to a relative path if asset generation fails
            // This should work for most cases but won't go through Magento's static file processing
            return '/app/code/BitBabit/DeveloperTools/view/frontend/web/js/profiler-widget.js';
        }
    }

    /**
     * Get CSS file URL with proper asset context
     * @return string
     */
    private function getCssUrl(): string
    {
        try {
            $params = [
                'area' => $this->getCurrentArea(),
                'theme' => $this->getCurrentTheme(),
                'locale' => $this->getCurrentLocale(),
                'module' => 'BitBabit_DeveloperTools'
            ];

            $asset = $this->assetRepository->createAsset(
                'BitBabit_DeveloperTools::css/profiler-widget.css',
                $params
            );

            return $asset->getUrl();
        } catch (\Exception $e) {
            return '/app/code/BitBabit/DeveloperTools/view/frontend/web/css/profiler-widget.css';
        }
    }

    /**
     * Get current area
     * @return string
     */
    private function getCurrentArea(): string
    {
        try {
            return $this->appState->getAreaCode();
        } catch (\Exception $e) {
            return 'frontend'; // Default to frontend
        }
    }

    /**
     * Get current theme
     * @return string
     */
    private function getCurrentTheme(): string
    {
        try {
            $themeCode = $this->design->getDesignTheme()->getCode();
            return $themeCode ? (string) $themeCode : 'Magento/luma';
        } catch (\Exception $e) {
            return 'Magento/luma'; // Default theme
        }
    }

    /**
     * Get current locale
     * @return string
     */
    private function getCurrentLocale(): string
    {
        try {
            return $this->localeResolver->getLocale();
        } catch (\Exception $e) {
            return 'en_US'; // Default locale
        }
    }

    /**
     * Set profiler cookies
     * @return void
     */
    private function setProfilerCookies(): void
    {
        try {
            if (!$this->cookieManagerService->get()) {
                $this->cookieManagerService->set($this->config->getApiKey());
            }
        } catch (\Exception $e) {
            error_log("Developer Tools: Failed to set profiler cookies - " . $e->getMessage());
        }
    }

    /**
     * Convert header value to string or null
     * @param mixed $header
     * @return string|null
     */
    private function headerToString($header): ?string
    {
        if (!$header) {
            return null;
        }
        if (is_object($header) && method_exists($header, 'toString')) {
            $str = $header->toString();
        } else {
            $str = (string) $header;
        }
        return $str === '' ? null : $str;
    }
}