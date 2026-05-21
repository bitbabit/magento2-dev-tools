<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use BitBabit\DeveloperTools\Model\DebugInfo;

/**
 * Comprehensive profiler service for collecting request performance data.
 *
 * Implements ResetAfterRequestInterface so the Application Server (Swoole)
 * automatically resets per-request mutable state after each response.
 *
 * @package BitBabit\DeveloperTools\Service
 */
class ComprehensiveProfilerService implements ResetAfterRequestInterface
{
    private array $timers = [];
    private float $applicationStartTime;

    /**
     * Minimum profiler handle for this request (Zend DB profiler key).
     * Profiles with handle >= this value belong to the current request.
     * Uses handles instead of counting finished queries so unfinished boundary queries
     * and unset/filtered slots do not misalign SQL with params.
     */
    private int $queryProfileMinHandle = 0;

    private const REDACTED_HEADERS = [
        'authorization',
        'cookie',
        'set-cookie',
        'x-api-key',
        'x-debug-api-key',
        'x-csrf-token',
        'proxy-authorization',
    ];

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProfilerConfigInterface $config
     * @param RequestInterface $request
     * @param State $appState
     */
    public function __construct(
        private ResourceConnection $resourceConnection,
        private ProfilerConfigInterface $config,
        private RequestInterface $request,
        private State $appState
    ) {
        $this->applicationStartTime = microtime(true);
        $this->startTimer('application_boot');
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->timers = [];
        $this->applicationStartTime = microtime(true);
        $this->queryProfileMinHandle = 0;
        DebugInfo::getInstance()->clear();
    }

    /**
     * Reset profiler state for a new request.
     * Must be called at the start of each HTTP request in long-lived processes
     * (Swoole, RoadRunner, FrankenPHP) where the service instance is reused.
     */
    public function resetForNewRequest(): void
    {
        $this->applicationStartTime = microtime(true);
        $this->timers = [];
        $this->startTimer('application_boot');
        DebugInfo::getInstance()->clear();

        try {
            $connection = $this->resourceConnection->getConnection();
            $profiler = $connection->getProfiler();
            // Include unfinished profiles when computing max handle so the boundary does not
            // drift vs count(getQueryProfiles()) when queries span the request edge or slots are unset.
            $profiles = $profiler->getQueryProfiles(null, true);
            if ($profiles && \count($profiles) > 0) {
                $this->queryProfileMinHandle = max(array_keys($profiles)) + 1;
            } else {
                $this->queryProfileMinHandle = 0;
            }
        } catch (\Exception $e) {
            $this->queryProfileMinHandle = 0;
        }
    }

    /**
     * @param string $name
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
     * @param string $name
     */
    public function endTimer(string $name): void
    {
        if (isset($this->timers[$name])) {
            $this->timers[$name]['end'] = microtime(true);
            $this->timers[$name]['duration'] = $this->timers[$name]['end'] - $this->timers[$name]['start'];
        }
    }

    /**
     * @return array
     */
    public function getComprehensiveData(): array
    {
        $this->endTimer('application_boot');
        $databaseData = $this->getDatabaseData();
        return [
            'overview' => $this->getOverviewData($databaseData),
            'database' => $databaseData,
            'request' => $this->getRequestData(),
            'performance' => $this->getPerformanceData(),
            'memory' => $this->getMemoryData(),
            'environment' => $this->getEnvironmentData(),
            'timers' => $this->getTimersData(),
            'metadata' => $this->getMetadata(),
            'debug_info' => DebugInfo::getInstance()->getData()
        ];
    }

    /**
     * @param array $dbData Pre-computed database data to avoid redundant profiler iteration
     * @return array
     */
    private function getOverviewData(array $dbData): array
    {
        return [
            'total_queries' => $dbData['total_queries'],
            'total_db_time' => $dbData['total_time_formatted'],
            'slow_queries_count' => $dbData['slow_queries_count'] ?? 0,
            'application_time' => $this->formatTime($this->getApplicationTime()),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'status' => $this->getOverallStatus($dbData)
        ];
    }

    /**
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
            foreach ($profiles as $handle => $profile) {
                if ((int) $handle < $this->queryProfileMinHandle) {
                    continue;
                }
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
     * @param string $header
     * @return string|null
     */
    public function getHeader(string $header): ?string
    {
        /** @var Request $request */
        $request = $this->request;
        $value = $request->getHeader($header);

        return $value !== false ? (string) $value : null;
    }

    /**
     * @return array
     */
    private function getRequestData(): array
    {
        /** @var Request $request */
        $request = $this->request;

        $postData = json_decode($request->getContent() ?? '[]', true);
        if ($postData === null) {
            $postData = $request->getPost()->toArray();
        }

        return [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'url' => $request->getUriString(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->getHeader('User-Agent') ?: null,
            'content_type' => $request->getHeader('Content-Type') ?: null,
            'headers' => $this->getSanitizedHeaders($request),
            'parameters' => [
                'GET' => $this->sanitizeParamArray($request->getQuery()->toArray()),
                'POST' => $this->sanitizeParamArray(is_array($postData) ? $postData : []),
                'FILES' => $request->getFiles()->toArray()
            ],
            'session' => $this->getSessionData(),
            'cookies' => '[redacted]'
        ];
    }

    /**
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
     * @return array
     */
    private function getEnvironmentData(): array
    {
        /** @var Request $request */
        $request = $this->request;

        return [
            'php_version' => PHP_VERSION,
            'server_software' => $request->getServer('SERVER_SOFTWARE') ?? 'Unknown',
            'operating_system' => PHP_OS,
            'max_execution_time' => ini_get('max_execution_time'),
            'timezone' => date_default_timezone_get(),
            'locale' => setlocale(LC_ALL, 0),
            'extensions' => array_slice(get_loaded_extensions(), 0, 20)
        ];
    }

    /**
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
     * @return array
     */
    private function getMetadata(): array
    {
        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'timestamp' => time(),
            'request_id' => uniqid('req_'),
            'profiler_version' => '1.2.3',
            'memory_limit_exceeded' => $this->isMemoryLimitExceeded()
        ];
    }

    /**
     * Sanitized inbound headers for debug logging (secrets redacted).
     *
     * @return array<string, string>
     */
    public function getSanitizedRequestHeaders(): array
    {
        /** @var Request $request */
        $request = $this->request;

        return $this->getSanitizedHeaders($request);
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getSanitizedHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $header) {
            $name = $header->getFieldName();
            if (in_array(strtolower($name), self::REDACTED_HEADERS, true)) {
                $headers[$name] = '[redacted]';
            } else {
                $headers[$name] = $header->getFieldValue();
            }
        }
        return $headers;
    }

    /**
     * Redact obvious secrets from captured GET/POST so profiler JSON/HTML does not leak credentials.
     *
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    private function sanitizeParamArray(array $params): array
    {
        $needles = [
            'password',
            'passwd',
            'token',
            'secret',
            'api_key',
            'apikey',
            'credit_card',
            'cvv',
            'authorization',
        ];
        $out = [];
        foreach ($params as $key => $value) {
            $lower = strtolower((string) $key);
            $sensitive = false;
            foreach ($needles as $n) {
                if (str_contains($lower, $n)) {
                    $sensitive = true;
                    break;
                }
            }
            if ($sensitive) {
                $out[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $out[$key] = $this->sanitizeParamArray($value);
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    /**
     * Retrieve a limited snapshot of session data via the request's DI-managed
     * server params rather than the $_SESSION superglobal.
     *
     * @return array
     */
    private function getSessionData(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return ['status' => 'No active session'];
        }

        try {
            $sessionId = session_id();
            return [
                'status' => 'active',
                'id_prefix' => $sessionId ? substr($sessionId, 0, 8) . '...' : null,
            ];
        } catch (\Exception $e) {
            return ['status' => 'Error reading session'];
        }
    }

    /**
     * @return float milliseconds
     */
    private function getApplicationTime(): float
    {
        return (microtime(true) - $this->applicationStartTime) * 1000;
    }

    /**
     * @return string
     */
    private function getBootstrapTime(): string
    {
        $bootstrapTime = $this->timers['application_boot']['duration'] ?? 0;
        return $this->formatTime($bootstrapTime * 1000);
    }

    /**
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
     * @param array $dbData
     * @return string
     */
    private function getOverallStatus(array $dbData): string
    {
        if ($dbData['slow_queries_count'] > 5) return 'warning';
        if ($dbData['total_queries'] > 100) return 'warning';

        return 'good';
    }

    /**
     * @return bool
     */
    private function isMemoryLimitExceeded(): bool
    {
        $currentMemoryMb = memory_get_usage(true) / 1024 / 1024;
        return $currentMemoryMb > $this->config->getMemoryLimitMb();
    }

    /**
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
}
