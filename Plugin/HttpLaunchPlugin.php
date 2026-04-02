<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Plugin;

use Magento\Framework\AppInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\App\ResourceConnection;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use BitBabit\DeveloperTools\Helper\Debug;
use BitBabit\DeveloperTools\Service\ComprehensiveProfilerService;

/**
 * Plugin that starts profiling at the very beginning of an HTTP request.
 *
 * Registered on both Magento\Framework\App\Http (php-fpm) and
 * Magento\ApplicationServer\App\Application (Swoole) so it fires
 * regardless of the server process model.
 *
 * @package BitBabit\DeveloperTools\Plugin
 */
class HttpLaunchPlugin
{
    /**
     * @param ProfilerConfigInterface $config
     * @param ResourceConnection $resourceConnection
     * @param Request $request
     * @param ComprehensiveProfilerService $profilerService
     * @param State $appState
     */
    public function __construct(
        private ProfilerConfigInterface $config,
        private ResourceConnection $resourceConnection,
        private Request $request,
        private ComprehensiveProfilerService $profilerService,
        private State $appState
    ) {}

    /**
     * @param AppInterface $subject
     * @return array
     */
    public function beforeLaunch(AppInterface $subject): array
    {
        if (php_sapi_name() === 'cli' && !$this->hasHttpRequestContext()) {
            return [];
        }

        if ($this->config->shouldProfileRequest($this->request)) {
            $this->profilerService->resetForNewRequest();
            Debug::startTimer('http_request');

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
     * Detect a live HTTP request (works for any app server: Swoole, RoadRunner, FrankenPHP, etc.)
     * @return bool
     */
    private function hasHttpRequestContext(): bool
    {
        try {
            $uri = $this->request->getRequestUri();
            $method = $this->request->getMethod();
            return !empty($uri) && !empty($method);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function isDeveloperMode(): bool
    {
        try {
            return $this->appState->getMode() === State::MODE_DEVELOPER;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function isMemoryLimitExceeded(): bool
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $memoryLimit = $this->config->getMemoryLimitMb();
        return $memoryUsage > $memoryLimit;
    }

    /**
     * @return void
     */
    private function enableProfiler(): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $profiler = $connection->getProfiler();
            if ($profiler) {
                $profiler->setEnabled(true);
                Debug::info('Database profiler successfully enabled');
            } else {
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
