<?php
declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Plugin;

use Magento\Framework\App\Http;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\App\ResourceConnection;
use VelocityDev\DeveloperTools\Api\ProfilerConfigInterface;
use VelocityDev\DeveloperTools\Helper\Debug;

/** 
 * HttpLaunchPlugin - Simplified version without session dependencies
 * @package VelocityDev\DeveloperTools\Plugin
 */
class HttpLaunchPlugin
{
    /**
     * HttpLaunchPlugin constructor
     * @param ProfilerConfigInterface $config
     * @param ResourceConnection $resourceConnection
     * @param Request $request
     */
    public function __construct(
        private ProfilerConfigInterface $config,
        private ResourceConnection $resourceConnection,
        private Request $request
    ) {}

    /**
     * beforeLaunch
     * @param Http $subject
     * @return array
     */
    public function beforeLaunch(Http $subject): array
    {
        // Skip profiling for CLI and cron contexts
        if (php_sapi_name() === 'cli') {
            return [];
        }

        if ($this->config->shouldProfileRequest($this->request)) {
            Debug::startTimer('http_request');

            // Check memory limit before enabling database profiler
            if (!$this->isMemoryLimitExceeded()) {
                $this->enableProfiler();
                Debug::info('Database profiler enabled', [
                    'memory_limit_mb' => $this->config->getMemoryLimitMb(),
                    'current_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
                ]);
            } else {
                Debug::warning('Profiler disabled due to memory limit', [
                    'memory_limit_mb' => $this->config->getMemoryLimitMb(),
                    'current_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
                ]);
            }
        } else {
            Debug::info('HTTP Request profiling skipped', [
                'method' => $this->request->getMethod(),
                'uri' => $this->request->getRequestUri(),
                'reason' => $this->getSkipReason()
            ]);
        }

        return [];
    }

    /**
     * Get reason why profiling was skipped
     * @return string
     */
    private function getSkipReason(): string
    {
        if (!$this->config->isEnabled()) {
            return 'Developer tools disabled in configuration';
        }

        if ($this->config->isDeveloperModeOnly() && !$this->isDeveloperMode()) {
            return 'Developer mode required but not active';
        }

        if (!$this->config->validateApiKey($this->request)) {
            return 'API key validation failed';
        }

        if (!$this->request->getHeader($this->config->getProfilerHeaderKey())) {
            return 'Profiler header not present';
        }

        return 'Unknown reason';
    }

    /**
     * Check if application is in developer mode
     * @return bool
     */
    private function isDeveloperMode(): bool
    {
        try {
            return class_exists(\Magento\Framework\App\State::class) && 
                   \Magento\Framework\App\ObjectManager::getInstance()
                       ->get(\Magento\Framework\App\State::class)
                       ->getMode() === \Magento\Framework\App\State::MODE_DEVELOPER;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if memory limit is exceeded
     * @return bool
     */
    private function isMemoryLimitExceeded(): bool
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // Convert to MB
        $memoryLimit = $this->config->getMemoryLimitMb();
        return $memoryUsage > $memoryLimit;
    }

    /**
     * Enable database profiler with compatibility checks
     * @return void
     */
    private function enableProfiler(): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $profiler = $connection->getProfiler();
            if($profiler){
                $profiler->setEnabled(true);
                Debug::info('Database profiler successfully enabled');
            }else{
                Debug::warning('Database connection does not support getProfiler method');
            }
            
        } catch (\Exception $e) {
            Debug::error('Failed to enable database profiler', [
                'error' => $e->getMessage(),
                'class' => get_class($e)
            ]);
        }
    }
}