<?php

declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Service;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Psr\Log\LoggerInterface;

/**
 * ApiKeyCookieManagerService
 * @package VelocityDev\DeveloperTools\Service
 * @author VelocityDev
 * @copyright 2025 VelocityDev
 * @license MIT
 * @version 1.0.0
 */
class ApiKeyCookieManagerService
{
    public const API_KEY_COOKIE_NAME = 'x_api_key';
    public const API_KEY_COOKIE_DURATION = 3600;
    public const API_KEY_COOKIE_HTTP_ONLY = false; // Changed to false -HttpOnly cookies can't be read by JS
    public const API_KEY_COOKIE_SECURE = false; // Will be set dynamically based on HTTPS
    public const API_KEY_COOKIE_PATH = '/';

    /**
     * Constructor
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Request $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly Request $request,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get a cookie value
     * @return string|null
     */
    public function get(): ?string
    {
        try {
            return $this->cookieManager->getCookie(self::API_KEY_COOKIE_NAME);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get API key cookie', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set a public cookie
     * @param string $value
     * @return bool Success status
     */
    public function set(string $value): bool 
    {
        try {
            // Create cookie metadata with proper method calls
            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
            
            // Set cookie properties using individual method calls
            $metadata->setDuration(self::API_KEY_COOKIE_DURATION);
            $metadata->setPath(self::API_KEY_COOKIE_PATH);
            $metadata->setHttpOnly(self::API_KEY_COOKIE_HTTP_ONLY);
            $metadata->setSecure($this->isSecureConnection());
            $metadata->setSameSite('Lax'); // Changed from Strict to Lax for better compatibility
            
            // Set the cookie
            $this->cookieManager->setPublicCookie(self::API_KEY_COOKIE_NAME, $value, $metadata);
            
            $this->logger->debug('API key cookie set successfully', [
                'cookie_name' => self::API_KEY_COOKIE_NAME,
                'secure' => $this->isSecureConnection(),
                'duration' => self::API_KEY_COOKIE_DURATION
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to set API key cookie', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Delete a cookie
     * @param string|null $name Cookie name (optional, defaults to API key cookie)
     * @return bool Success status
     */
    public function delete(?string $name = null): bool
    {
        try {
            $cookieName = $name ?? self::API_KEY_COOKIE_NAME;
            
            // Create cookie metadata for deletion
            $metadata = $this->cookieMetadataFactory->createCookieMetadata();
            $metadata->setPath(self::API_KEY_COOKIE_PATH);
            
            // Delete the cookie
            $this->cookieManager->deleteCookie($cookieName, $metadata);
            
            $this->logger->debug('Cookie deleted successfully', [
                'cookie_name' => $cookieName
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete cookie', [
                'cookie_name' => $name ?? self::API_KEY_COOKIE_NAME,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate the API key cookie value
     * @param string|null $expectedApiKey
     * @return bool
     */
    public function validate(?string $expectedApiKey): bool
    {
        if (!$expectedApiKey) {
            $this->logger->warning('No expected API key provided for validation');
            return false;
        }

        $cookieValue = $this->get();
        if (!$cookieValue) {
            $this->logger->debug('No API key cookie found for validation');
            return false;
        }

        // Use hash_equals to prevent timing attacks
        $isValid = hash_equals($expectedApiKey, $cookieValue);
        
        $this->logger->debug('API key validation result', [
            'is_valid' => $isValid
        ]);
        
        return $isValid;
    }

    /**
     * Check if the cookie exists
     * @return bool
     */
    public function exists(): bool
    {
        return $this->get() !== null;
    }

    /**
     * Set cookie with custom options
     * @param string $value
     * @param array $options Custom options
     * @return bool
     */
    public function setWithOptions(string $value, array $options = []): bool
    {
        try {
            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
            
            // Set duration
            $duration = $options['duration'] ?? self::API_KEY_COOKIE_DURATION;
            $metadata->setDuration($duration);
            
            // Set path
            $path = $options['path'] ?? self::API_KEY_COOKIE_PATH;
            $metadata->setPath($path);
            
            // Set HttpOnly
            $httpOnly = $options['http_only'] ?? self::API_KEY_COOKIE_HTTP_ONLY;
            $metadata->setHttpOnly($httpOnly);
            
            // Set Secure (auto-detect if not specified)
            $secure = $options['secure'] ?? $this->isSecureConnection();
            $metadata->setSecure($secure);
            
            // Set SameSite
            $sameSite = $options['same_site'] ?? 'Lax';
            $metadata->setSameSite($sameSite);
            
            // Set domain if specified
            if (isset($options['domain'])) {
                $metadata->setDomain($options['domain']);
            }
            
            $this->cookieManager->setPublicCookie(self::API_KEY_COOKIE_NAME, $value, $metadata);
            
            $this->logger->debug('API key cookie set with custom options', [
                'options' => $options
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to set API key cookie with custom options', [
                'options' => $options,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if the current connection is secure (HTTPS)
     * @return bool
     */
    private function isSecureConnection(): bool
    {
        // Check if request is secure
        if ($this->request->isSecure()) {
            return true;
        }
        
        // Additional checks for reverse proxy scenarios
        $httpsHeader = $this->request->getServer('HTTPS');
        if ($httpsHeader && $httpsHeader !== 'off') {
            return true;
        }
        
        $forwardedProto = $this->request->getServer('HTTP_X_FORWARDED_PROTO');
        if ($forwardedProto === 'https') {
            return true;
        }
        
        $serverPort = $this->request->getServer('SERVER_PORT');
        if ($serverPort && (int)$serverPort === 443) {
            return true;
        }
        
        return false;
    }

    /**
     * Get cookie information for debugging
     * @return array
     */
    public function getDebugInfo(): array
    {
        return [
            'cookie_name' => self::API_KEY_COOKIE_NAME,
            'exists' => $this->exists(),
            'value_length' => $this->get() ? strlen($this->get()) : 0,
            'is_secure_connection' => $this->isSecureConnection(),
            'current_domain' => $this->request->getServer('HTTP_HOST'),
            'current_path' => $this->request->getServer('REQUEST_URI')
        ];
    }
}