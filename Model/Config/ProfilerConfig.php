<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Math\Random;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use BitBabit\DeveloperTools\Service\ApiKeyCookieManagerService;
use Psr\Log\LoggerInterface;

/**
 * ProfilerConfig
 * @package BitBabit\DeveloperTools\Model\Config
 */
class ProfilerConfig implements ProfilerConfigInterface
{
    /**
     * ProfilerConfig constructor
     * @param ScopeConfigInterface $scopeConfig
     * @param State $appState
     * @param Random $mathRandom
     * @param ApiKeyCookieManagerService $cookieManagerService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private State $appState,
        private Random $mathRandom,
        private ApiKeyCookieManagerService $cookieManagerService,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Is enabled
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED);
    }
    

    /**
     * Get the profiler header key
     * @return string
     */
    public function getProfilerHeaderKey(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HEADER_KEY) ?: self::DEFAULT_HEADER_KEY;
    }

    /**
     * Is HTML output enabled
     * @return bool
     */
    public function isHtmlOutputEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_HTML_OUTPUT);
    }

    /**
     * Is JSON injection enabled
     * @return bool
     */
    public function isJsonInjectionEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_JSON_INJECTION);
    }

    /**
     * isLogToFileEnabled
     * @return bool
     */
    public function isLogToFileEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_LOG_TO_FILE);
    }

    public function isDebugLoggingEnabled(): bool
    {
        return $this->isEnabled() && $this->isLogToFileEnabled();
    }

    /**
     * isDeveloperModeOnly
     * @return bool
     */
    public function isDeveloperModeOnly(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DEVELOPER_MODE_ONLY);
    }
    
    /**
     * getSlowQueryThreshold
     * @return int
     */
    public function getSlowQueryThreshold(): int
    {
        return (int)($this->scopeConfig->getValue(self::XML_PATH_SLOW_QUERY_THRESHOLD) ?: self::DEFAULT_SLOW_QUERY_THRESHOLD);
    }
    
    /**
     * Is toolbar widget enabled
     * @return bool
     */
    public function isToolbarWidgetEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_TOOLBAR_WIDGET);
    }
    
    /**
     * Get the memory limit in MB
     * @return int
     */
    public function getMemoryLimitMb(): int
    {
        return (int)($this->scopeConfig->getValue(self::XML_PATH_MEMORY_LIMIT) ?: self::DEFAULT_MEMORY_LIMIT);
    }
    
    /**
     * Check if API key validation is enabled
     * @return bool
     */
    public function isApiKeyEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_API_KEY_ENABLED);
    }

    /**
     * Get the configured API key
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        $apiKey = $this->scopeConfig->getValue(self::XML_PATH_API_KEY);
        return $apiKey ? (string)$apiKey : null;
    }

    /**
     * Validate API key from request header or URL parameter
     * @param Request $request
     * @return bool
     */
    public function validateApiKey(Request $request): bool
    {
        // If API key validation is disabled, always return true
        if (!$this->isApiKeyEnabled()) {
            return true;
        }

        $configuredApiKey = $this->getApiKey();
        if (!$configuredApiKey) {
            // No API key configured, deny access
            return false;
        }

        // Try to get API key from header first
        $requestApiKey = $request->getHeader(self::API_KEY_HEADER);
        // If not in header, try URL parameter (for initial handshake)
        if (!$requestApiKey) {
            $requestApiKey = $request->getParam('api_key');
        }

        // If not in header or URL, try cookie
        if (!$requestApiKey) {
            $requestApiKey = $this->cookieManagerService->get();
        }
        if (!$requestApiKey) {
            // No API key in request or URL, deny access
            return false;
        }

        // Use timing-safe comparison to prevent timing attacks
        return hash_equals($configuredApiKey, $requestApiKey);
    }

    /**
     * Generate a new API key
     * @return string
     */
    public function generateApiKey(): string
    {
        try {
            // Generate a 32-character random string
            return $this->mathRandom->getRandomString(32, Random::CHARS_LOWERS . Random::CHARS_UPPERS . Random::CHARS_DIGITS);
        } catch (\Exception $e) {
            // Fallback to simple random generation
            return bin2hex(random_bytes(16));
        }
    }

    public function shouldProfileRequest(Request $request): bool
    {
        return $this->getProfilingGateResult($request)['allowed'];
    }

    /**
     * @inheritDoc
     */
    public function getProfilingGateResult(Request $request): array
    {
        $headerStatus = $this->buildHeaderStatus($request);

        if (!$this->isEnabled()) {
            $this->logGate('deny', ['reason' => 'module_disabled']);

            return $this->denyResult('module_disabled', [], $headerStatus, false);
        }

        if ($this->isDeveloperModeOnly() && $this->appState->getMode() !== State::MODE_DEVELOPER) {
            $this->logGate('deny', ['reason' => 'developer_mode_required']);

            return $this->denyResult('developer_mode_required', [], $headerStatus, false);
        }

        if (!$this->validateApiKey($request)) {
            $missing = $this->isApiKeyEnabled() ? [self::API_KEY_HEADER] : [];
            $this->logGate('deny', [
                'reason' => 'api_key',
                'api_key_validation_enabled' => $this->isApiKeyEnabled(),
                'configured_key_present' => $this->getApiKey() !== null,
                'missing_headers' => $missing,
            ]);

            return $this->denyResult('api_key', $missing, $headerStatus, true);
        }

        $headerKey = $this->getProfilerHeaderKey();
        $headerValue = $request->getHeader($headerKey);
        if ($headerValue === false) {
            $this->logGate('deny', ['reason' => 'profiler_header_missing', 'header' => $headerKey]);

            return $this->denyResult('profiler_header_missing', [$headerKey], $headerStatus, true);
        }

        $headerValue = trim((string) $headerValue);
        if ($headerValue === '') {
            $this->logGate('deny', ['reason' => 'profiler_header_empty', 'header' => $headerKey]);

            return $this->denyResult('profiler_header_empty', [$headerKey], $headerStatus, true);
        }

        $this->logGate('allow', ['profiler_trigger_header' => $headerKey]);

        return [
            'allowed' => true,
            'reason' => null,
            'missing_headers' => [],
            'header_status' => $headerStatus,
            'should_log_request_headers' => false,
        ];
    }

    /**
     * @return array<string, string> present|missing|empty
     */
    private function buildHeaderStatus(Request $request): array
    {
        $names = array_unique([
            $this->getProfilerHeaderKey(),
            self::API_KEY_HEADER,
            'X-SSR-ID',
            'X-SSR-Source',
        ]);
        $status = [];
        foreach ($names as $name) {
            $value = $request->getHeader($name);
            if ($value === false) {
                $status[$name] = 'missing';
            } elseif (trim((string) $value) === '') {
                $status[$name] = 'empty';
            } else {
                $status[$name] = 'present';
            }
        }

        return $status;
    }

    /**
     * @param list<string> $missingHeaders
     * @param array<string, string> $headerStatus
     * @return array<string, mixed>
     */
    private function denyResult(
        string $reason,
        array $missingHeaders,
        array $headerStatus,
        bool $logRequestHeaders
    ): array {
        return [
            'allowed' => false,
            'reason' => $reason,
            'missing_headers' => $missingHeaders,
            'header_status' => $headerStatus,
            'should_log_request_headers' => $logRequestHeaders && $this->isDebugLoggingEnabled(),
        ];
    }

    /**
     * @param string $outcome allow|deny
     * @param array<string, mixed> $context
     */
    private function logGate(string $outcome, array $context = []): void
    {
        if (!$this->isDebugLoggingEnabled()) {
            return;
        }

        $this->logger->debug('Profiler gate: ' . $outcome, $context);
    }
}