<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Plugin;

use Magento\Framework\AppInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\App\ResourceConnection;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use BitBabit\DeveloperTools\Service\ComprehensiveProfilerService;
use BitBabit\DeveloperTools\Service\DebugLogger;

/**
 * Starts DB profiler early for HTTP-style requests.
 *
 * @package BitBabit\DeveloperTools\Plugin
 */
class HttpLaunchPlugin
{
    public function __construct(
        private ProfilerConfigInterface $config,
        private ResourceConnection $resourceConnection,
        private Request $request,
        private ComprehensiveProfilerService $comprehensiveProfiler,
        private DebugLogger $debugLogger
    ) {
    }

    /**
     * @param AppInterface $subject
     * @return array
     */
    public function beforeLaunch(AppInterface $subject): array
    {
        if (php_sapi_name() === 'cli' && !$this->hasHttpRequestContext()) {
            return [];
        }

        $this->comprehensiveProfiler->resetForNewRequest();

        $gate = $this->config->getProfilingGateResult($this->request);

        if (!$gate['allowed']) {
            if ($gate['should_log_request_headers']) {
                $this->debugLogger->warning('Profiler enabled in admin but required headers missing; request headers', [
                    'deny_reason'       => $gate['reason'],
                    'missing_headers'   => $gate['missing_headers'],
                    'header_status'     => $gate['header_status'],
                    'uri'               => $this->request->getRequestUri(),
                    'method'            => $this->request->getMethod(),
                    'request_headers'   => $this->comprehensiveProfiler->getSanitizedRequestHeaders(),
                ]);
            }

            return [];
        }

        if (!$this->isMemoryLimitExceeded()) {
            $this->enableProfiler();
            $this->debugLogger->info('Database profiler enabled', [
                'memory_limit_mb'   => $this->config->getMemoryLimitMb(),
                'current_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ]);
        } else {
            $this->debugLogger->warning('Profiler disabled due to memory limit', [
                'memory_limit_mb'   => $this->config->getMemoryLimitMb(),
                'current_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ]);
        }

        return [];
    }

    private function hasHttpRequestContext(): bool
    {
        try {
            $uri    = $this->request->getRequestUri();
            $method = $this->request->getMethod();
            return $uri !== '' && $method !== '';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isMemoryLimitExceeded(): bool
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $memoryLimit = $this->config->getMemoryLimitMb();
        return $memoryUsage > $memoryLimit;
    }

    private function enableProfiler(): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $profiler   = $connection->getProfiler();
            if ($profiler) {
                $profiler->setEnabled(true);
                $this->debugLogger->info('Database profiler successfully enabled');
            } else {
                $this->debugLogger->warning('Database connection does not support getProfiler method');
            }
        } catch (\Exception $e) {
            $this->debugLogger->error('Failed to enable database profiler', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
        }
    }
}
