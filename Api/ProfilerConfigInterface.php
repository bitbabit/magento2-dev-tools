<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Api;

/**
 * ProfilerConfigInterface
 * @package BitBabit\DeveloperTools\Api
 */
interface ProfilerConfigInterface
{
    /**
     * XML path for the profiler status
     */
    public const XML_PATH_ENABLED = 'bitbabit/dev_tools/enabled';
    

    /**
     * XML path for the profiler header key
     */
    public const XML_PATH_HEADER_KEY = 'bitbabit/dev_tools/profiler_header_key';

    /**
     * XML path for the API key validation
     */
    public const XML_PATH_API_KEY_ENABLED = 'bitbabit/dev_tools/api_key_enabled';

    /**
     * XML path for the API key
     */
    public const XML_PATH_API_KEY = 'bitbabit/dev_tools/api_key';

    /**
     * API key header name
     */
    public const API_KEY_HEADER = 'X-Debug-Api-Key';

    /**
     * XML path for the HTML output
     */
    public const XML_PATH_HTML_OUTPUT = 'bitbabit/dev_tools/html_output_enabled';

    /**
     * XML path for the JSON injection
     */
    public const XML_PATH_JSON_INJECTION = 'bitbabit/dev_tools/json_injection_enabled';

    /**
     * XML path for the log to file
     */
    public const XML_PATH_LOG_TO_FILE = 'bitbabit/dev_tools/log_to_file_enabled';

    /**
     * XML path for the developer mode only
     */
    public const XML_PATH_DEVELOPER_MODE_ONLY = 'bitbabit/dev_tools/developer_mode_only';

    /**
     * XML path for the slow query threshold
     */
    public const XML_PATH_SLOW_QUERY_THRESHOLD = 'bitbabit/dev_tools/slow_query_threshold';

    /**
     * XML path for the toolbar widget
     */
    public const XML_PATH_TOOLBAR_WIDGET = 'bitbabit/dev_tools/toolbar_widget_enabled';

    /**
     * XML path for the memory limit
     */
    public const XML_PATH_MEMORY_LIMIT = 'bitbabit/dev_tools/memory_limit_mb';

    /**
     * Default header key
     */
    public const DEFAULT_HEADER_KEY = 'X-Debug-Mode';

    /**
     * Default slow query threshold
     */
    public const DEFAULT_SLOW_QUERY_THRESHOLD = 100;

    /**
     * Default memory limit
     */
    public const DEFAULT_MEMORY_LIMIT = 512;

    /**
     * Check if the profiler is enabled
     */
    public function isEnabled(): bool;


    /**
     * Get the profiler header key
     */
    public function getProfilerHeaderKey(): string;

    /**
     * Check if the HTML output is enabled
     */
    public function isHtmlOutputEnabled(): bool;

    /**
     * Check if the JSON injection is enabled
     */
    public function isJsonInjectionEnabled(): bool;

    public function isLogToFileEnabled(): bool;

    /**
     * Check if the developer mode only is enabled
     */
    public function isDeveloperModeOnly(): bool;

    /**
     * Get the slow query threshold
     */
    public function getSlowQueryThreshold(): int;

    /**
     * Check if the toolbar widget is enabled
     */
    public function isToolbarWidgetEnabled(): bool;

    /**
     * Get the memory limit in MB
     */
    public function getMemoryLimitMb(): int;

    /**
     * Check if API key validation is enabled
     */
    public function isApiKeyEnabled(): bool;

    /**
     * Get the configured API key
     */
    public function getApiKey(): ?string;

    /**
     * Validate API key from request header
     */
    public function validateApiKey(\Magento\Framework\HTTP\PhpEnvironment\Request $request): bool;

    /**
     * Generate a new API key
     */
    public function generateApiKey(): string;

    /**
     * Check if the request should be profiled
     */
    public function shouldProfileRequest(\Magento\Framework\HTTP\PhpEnvironment\Request $request): bool;
}