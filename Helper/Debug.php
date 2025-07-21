<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Helper;

use BitBabit\DeveloperTools\Model\DebugInfo;

/**
 * Debug helper for easy access to DebugInfo singleton
 * @package BitBabit\DeveloperTools\Helper
 */
class Debug
{
    /**
     * Add a debug message
     * @param string $message
     * @param string $level
     * @param array $context
     */
    public static function log(string $message, string $level = 'info', array $context = []): void
    {
        DebugInfo::getInstance()->addMessage($message, $level, $context);
    }

    /**
     * Add an info message
     * @param string $message
     * @param array $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::log($message, 'info', $context);
    }

    /**
     * Add a warning message
     * @param string $message
     * @param array $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log($message, 'warning', $context);
    }

    /**
     * Add an error message
     * @param string $message
     * @param array $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::log($message, 'error', $context);
    }



    /**
     * Start a timer (legacy method - currently disabled)
     * @param string $name
     */
    public static function startTimer(string $name): void
    {
        // Timer functionality removed for simplicity
        // Use regular debug messages with timestamps instead
        self::info("Timer started: {$name}", ['timer_name' => $name, 'action' => 'start']);
    }

    /**
     * End a timer (legacy method - currently disabled)
     * @param string $name
     * @param string $message
     */
    public static function endTimer(string $name, string $message = ''): void
    {
        // Timer functionality removed for simplicity
        // Use regular debug messages with timestamps instead
        $msg = $message ?: "Timer ended: {$name}";
        self::info($msg, ['timer_name' => $name, 'action' => 'end']);
    }

    /**
     * Dump variable as debug message
     * @param mixed $var
     * @param string $label
     */
    public static function dump($var, string $label = 'Variable dump'): void
    {
        self::log($label, 'dump', ['data' => $var]);
    }
} 