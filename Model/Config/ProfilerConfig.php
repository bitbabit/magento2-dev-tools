<?php
declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Math\Random;
use VelocityDev\DeveloperTools\Api\ProfilerConfigInterface;
use VelocityDev\DeveloperTools\Service\ApiKeyCookieManagerService;

/**
 * ProfilerConfig
 * @package VelocityDev\DeveloperTools\Model\Config
 */
class ProfilerConfig implements ProfilerConfigInterface
{
    /**
     * ProfilerConfig constructor
     * @param ScopeConfigInterface $scopeConfig
     * @param State $appState
     * @param Random $mathRandom
     * @param ApiKeyCookieManagerService $cookieManagerService
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private State $appState,
        private Random $mathRandom,
        private ApiKeyCookieManagerService $cookieManagerService
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

    /**
     * Should enable profiler request
     * @param Request $request
     * @return bool
     */
    public function shouldProfileRequest(Request $request): bool
    {
        // Check all false conditions first
        if (!$this->isEnabled() || 
            ($this->isDeveloperModeOnly() && $this->appState->getMode() !== State::MODE_DEVELOPER) ||
            !$this->validateApiKey($request)) {
            return false;
        }
        // Return true if show without header key, or if profiler header is present
        return true;
    }


}