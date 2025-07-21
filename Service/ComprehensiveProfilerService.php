<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use BitBabit\DeveloperTools\Model\DebugInfo;

/**
 * ComprehensiveProfilerService
 * @package BitBabit\DeveloperTools\Service
 */
class ComprehensiveProfilerService
{
    /**
     * @var array
     */
    private array $timers = [];
    
    /**
     * @var float
     */
    private float $applicationStartTime;
    
    /**
     * @var array
     */
    private array $collectectedData = [];

    /**
     * ComprehensiveProfilerService constructor
     * @param ResourceConnection $resourceConnection
     * @param ProfilerConfigInterface $config
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param State $appState
     */
    public function __construct(
        private ResourceConnection $resourceConnection,
        private ProfilerConfigInterface $config,
        private LoggerInterface $logger,
        private RequestInterface $request,
        private State $appState
    ) {
        $this->applicationStartTime = defined('MAGENTO_ROOT') ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
        $this->startTimer('application_boot');
    }

    /**
     * Start timer
     * @param string $name
     * @return void
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'end' => null,
            'duration' => null
        ];
    }

    /**
     * End timer
     * @param string $name
     * @return void
     */
    public function endTimer(string $name): void
    {
        if (isset($this->timers[$name])) {
            $this->timers[$name]['end'] = microtime(true);
            $this->timers[$name]['duration'] = $this->timers[$name]['end'] - $this->timers[$name]['start'];
        }
    }

    /**
     * Get comprehensive data
     * @return array
     */
    public function getComprehensiveData(): array
    {
        $this->endTimer('application_boot');
        $debugInfo = DebugInfo::getInstance();
        $debugInfo->addMessage("test message", 'info', [
            'timer_name' => 'test',
            'duration' => 1000,
            'data' => [
                'test' => 'test',
                'test2' => 'test2',
                'beta' => [
                    'test3' => 'test3',
                    'test4' => 'test4'
                ]
            ]
        ]);
        return [
            'overview' => $this->getOverviewData(),
            'database' => $this->getDatabaseData(),
            'request' => $this->getRequestData(),
            'performance' => $this->getPerformanceData(),
            'memory' => $this->getMemoryData(),
            'environment' => $this->getEnvironmentData(),
            'timers' => $this->getTimersData(),
            'metadata' => $this->getMetadata(),
            'debug_info' => $this->getDebugInfo()
        ];
    }

    /**
     * Get overview data
     * @return array
     */
    private function getOverviewData(): array
    {
        $dbData = $this->getDatabaseData();
        return [
            'total_queries' => $dbData['total_queries'],
            'total_db_time' => $dbData['total_time_formatted'],
            'slow_queries_count' => $dbData['slow_queries_count'],
            'application_time' => $this->formatTime($this->getApplicationTime()),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'status' => $this->getOverallStatus($dbData)
        ];
    }

    /**
     * Get database data
     * @return array
     */
    private function getDatabaseData(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $profiler = $connection->getProfiler();
        
        if (!$profiler->getEnabled()) {
            return [
                'enabled' => false,
                'total_queries' => 0,
                'total_time' => 0,
                'total_time_formatted' => '0 ms',
                'queries' => [],
                'queries_by_type' => []
            ];
        }

        $queries = [];
        $totalTime = 0.0;
        $slowQueryThreshold = $this->config->getSlowQueryThreshold() / 1000;

        $profiles = $profiler->getQueryProfiles();
        if ($profiles) {
            foreach ($profiles as $profile) {
                $queryTime = $profile->getElapsedSecs();
                $query = [
                    'query' => $profile->getQuery(),
                    'time' => $queryTime,
                    'time_formatted' => $this->formatTime($queryTime * 1000),
                    'type' => $this->getQueryType($profile->getQuery()),
                    'params' => $profile->getQueryParams(),
                    'is_slow' => $queryTime > $slowQueryThreshold,
                ];
                $queries[] = $query;
                $totalTime += $queryTime;
            }
        }

        $queriesByType = array_count_values(array_column($queries, 'type'));

        return [
            'enabled' => true,
            'total_queries' => count($queries),
            'total_time' => $totalTime,
            'total_time_formatted' => $this->formatTime($totalTime * 1000),
            'queries' => $queries,
            'slow_queries_count' => count(array_filter($queries, fn($q) => $q['is_slow'])),
            'queries_by_type' => $queriesByType,
            'slow_query_threshold' => $slowQueryThreshold * 1000 . ' ms'
        ];
    }
    /**
     * Get request header
     * @param mixed $header
     * @return void
     */
    public function getHeader($header): ?string{
         /** @var Request $request */
         $request = $this->request;

         $value = $request->getHeader($header); 

         return $value?$value:null;
    }
    /**
     * Get request data
     * @return array
     */
    private function getRequestData(): array
    {
        /** @var Request $request */
        $request = $this->request;
        
        return [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'url' => $request->getUriString(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->getHeader('User-Agent'),
            'content_type' => $request->getHeader('Content-Type'),
            'headers' => $this->getRequestHeaders($request),
            'parameters' => [
                'GET' => $request->getQuery()->toArray(),
                'POST' => json_decode($request->getContent() ?? "[]",true),
                'FILES' => $_FILES ?? []
            ],
            'session' => $this->getSessionData(),
            'cookies' => $request->getCookie()
        ];
    }

    /**
     * Get performance data
     * @return array
     */
    private function getPerformanceData(): array
    {
        $applicationTime = $this->getApplicationTime();
        
        return [
            'application_time' => $this->formatTime($applicationTime),
            'application_time_ms' => round($applicationTime, 2),
            'bootstrap_time' => $this->getBootstrapTime(),
            'php_version' => PHP_VERSION,
            'magento_mode' => $this->appState->getMode(),
            'server_load' => sys_getloadavg(),
            'opcache' => $this->getOpcacheStatus()
        ];
    }

    /**
     * Get memory data
     * @return array
     */
    private function getMemoryData(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'current_usage_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak_usage' => memory_get_peak_usage(true),
            'peak_usage_formatted' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'real_usage' => memory_get_usage(false),
            'real_usage_formatted' => $this->formatBytes(memory_get_usage(false))
        ];
    }

    /**
     * Get environment data
     * @return array
     */ 
    private function getEnvironmentData(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS,
            'max_execution_time' => ini_get('max_execution_time'),
            'timezone' => date_default_timezone_get(),
            'locale' => setlocale(LC_ALL, 0),
            'extensions' => array_slice(get_loaded_extensions(), 0, 20) // Limit to first 20
        ];
    }

    /**
     * Get timers data
     * @return array
     */ 
    private function getTimersData(): array
    {
        $formattedTimers = [];
        foreach ($this->timers as $name => $timer) {
            $formattedTimers[$name] = [
                'duration' => $timer['duration'] ?? 0,
                'duration_formatted' => $this->formatTime(($timer['duration'] ?? 0) * 1000),
                'started_at' => date('H:i:s.u', (int)$timer['start']),
                'ended_at' => $timer['end'] ? date('H:i:s.u', (int)$timer['end']) : null
            ];
        }
        return $formattedTimers;
    }

    /**
     * Get metadata
     * @return array
     */     
    private function getMetadata(): array
    {
        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'timestamp' => time(),
            'request_id' => uniqid('req_'),
            'profiler_version' => '1.0.0',
            'memory_limit_exceeded' => $this->isMemoryLimitExceeded()
        ];
    }

    /**
     * Get request headers
     * @param Request $request
     * @return array
     */
    private function getRequestHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $header) {
            $headers[$header->getFieldName()] = $header->getFieldValue();
        }
        return $headers;
    }

    /**
     * Get session data
     * @return array
     */ 
    private function getSessionData(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return array_slice($_SESSION ?? [], 0, 10); // Limit session data
        }
        return ['status' => 'No active session'];
    }

    /**
     * Get application time
     * @return float
     */
    private function getApplicationTime(): float
    {
        return (microtime(true) - $this->applicationStartTime) * 1000; // Convert to milliseconds
    }

    /**
     * Get bootstrap time
     * @return string
     */
    private function getBootstrapTime(): string
    {
        $bootstrapTime = $this->timers['application_boot']['duration'] ?? 0;
        return $this->formatTime($bootstrapTime * 1000);
    }

    /**
     * Get opcache status
     * @return array
     */
    private function getOpcacheStatus(): array
    {
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status(false);
            return [
                'enabled' => $status !== false,
                'memory_usage' => $status['memory_usage'] ?? null,
                'hit_rate' => isset($status['opcache_statistics']) ? 
                    round($status['opcache_statistics']['opcache_hit_rate'], 2) : null
            ];
        }
        return ['enabled' => false];
    }

    /**
     * Get overall status
     * 
     * @return string
     */
    private function getOverallStatus($dbData): string
    {
        if ($dbData['slow_queries_count'] > 5) return 'warning';
        if ($dbData['total_queries'] > 100) return 'warning';
        
        return 'good';
    }

    /**
     * Check if memory limit is exceeded
     * @return bool
     */
    private function isMemoryLimitExceeded(): bool
    {
        $currentMemoryMb = memory_get_usage(true) / 1024 / 1024;
        return $currentMemoryMb > $this->config->getMemoryLimitMb();
    }

    /**
     * Format query
     * @param string $query
     * @return string
     */
    private function formatQuery(string $query): string
    {
        // Truncate very long queries and format them
        return strlen($query) > 200 ? substr($query, 0, 200) . '...' : $query;
    }

    /**
     * Get query type
     * @param string $query
     * @return string
     */
    private function getQueryType(string $query): string
    {
        $query = trim(strtoupper($query));
        
        if (str_starts_with($query, 'SELECT')) {
            return 'SELECT';
        } elseif (str_starts_with($query, 'INSERT')) {
            return 'INSERT';
        } elseif (str_starts_with($query, 'UPDATE')) {
            return 'UPDATE';
        } elseif (str_starts_with($query, 'DELETE')) {
            return 'DELETE';
        } elseif (str_starts_with($query, 'CREATE')) {
            return 'CREATE';
        } elseif (str_starts_with($query, 'ALTER')) {
            return 'ALTER';
        } elseif (str_starts_with($query, 'DROP')) {
            return 'DROP';
        } else {
            return 'OTHER';
        }
    }

    /**
     * Format time
     * @param float $milliseconds
     * @return string
     */
    private function formatTime(float $milliseconds): string
    {
        if ($milliseconds < 1) {
            return number_format($milliseconds * 1000, 2) . ' μs';
        } elseif ($milliseconds < 1000) {
            return number_format($milliseconds, 2) . ' ms';
        } else {
            return number_format($milliseconds / 1000, 2) . ' s';
        }
    }

    /**
     * Format bytes
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get debug info
     * @return array
     */
    private function getDebugInfo(): array
    {
        $debugInfo = DebugInfo::getInstance();
        return $debugInfo->getData();
    }
} 