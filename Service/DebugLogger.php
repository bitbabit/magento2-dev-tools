<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Service;

use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use BitBabit\DeveloperTools\Model\DebugInfo;
use Psr\Log\LoggerInterface;

/**
 * Writes developer-tools debug output only when enabled in admin
 * (Developer Tools enabled + Log debug messages to file).
 */
class DebugLogger
{
    public function __construct(
        private ProfilerConfigInterface $config,
        private LoggerInterface $logger
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isDebugLoggingEnabled();
    }

    public function log(string $message, string $level = 'info', array $context = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        DebugInfo::getInstance()->addMessage($message, $level, $context);

        match ($level) {
            'error' => $this->logger->error($message, $context),
            'warning' => $this->logger->warning($message, $context),
            'debug', 'dump' => $this->logger->debug($message, $context),
            default => $this->logger->info($message, $context),
        };
    }

    public function info(string $message, array $context = []): void
    {
        $this->log($message, 'info', $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log($message, 'warning', $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log($message, 'error', $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log($message, 'debug', $context);
    }

    public function gate(string $outcome, array $context = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->log('Profiler gate: ' . $outcome, 'debug', $context);
    }
}
