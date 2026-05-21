<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Helper;

use BitBabit\DeveloperTools\Service\DebugLogger;

/**
 * @deprecated Use {@see DebugLogger} (injected). Kept for backward compatibility.
 */
class Debug
{
    public function __construct(
        private DebugLogger $debugLogger
    ) {
    }

    public function log(string $message, string $level = 'info', array $context = []): void
    {
        $this->debugLogger->log($message, $level, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->debugLogger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->debugLogger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->debugLogger->error($message, $context);
    }

    public function startTimer(string $name): void
    {
        // Timers are tracked in ComprehensiveProfilerService.
    }

    public function endTimer(string $name, string $message = ''): void
    {
        // Timers are tracked in ComprehensiveProfilerService.
    }

    public function dump($var, string $label = 'Variable dump'): void
    {
        $this->debugLogger->log($label, 'dump', ['data' => $var]);
    }
}
