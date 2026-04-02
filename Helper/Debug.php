<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Helper;

use BitBabit\DeveloperTools\Model\DebugInfo;
use Magento\Framework\App\ObjectManager;

/**
 * Static debug helper that bridges to the DI-managed DebugInfo service.
 *
 * Uses ObjectManager internally so the static API (Debug::info(), etc.)
 * resolves the same shared DebugInfo instance that the Application Server
 * resets via ResetAfterRequestInterface after each request.
 *
 * @package BitBabit\DeveloperTools\Helper
 */
class Debug
{
    /**
     * @return DebugInfo
     */
    private static function getDebugInfo(): DebugInfo
    {
        return ObjectManager::getInstance()->get(DebugInfo::class);
    }

    /**
     * @param string $message
     * @param string $level
     * @param array $context
     */
    public static function log(string $message, string $level = 'info', array $context = []): void
    {
        self::getDebugInfo()->addMessage($message, $level, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::log($message, 'info', $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log($message, 'warning', $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::log($message, 'error', $context);
    }

    /**
     * @param string $name
     */
    public static function startTimer(string $name): void
    {
        self::info("Timer started: {$name}", ['timer_name' => $name, 'action' => 'start']);
    }

    /**
     * @param string $name
     * @param string $message
     */
    public static function endTimer(string $name, string $message = ''): void
    {
        $msg = $message ?: "Timer ended: {$name}";
        self::info($msg, ['timer_name' => $name, 'action' => 'end']);
    }

    /**
     * @param mixed $var
     * @param string $label
     */
    public static function dump($var, string $label = 'Variable dump'): void
    {
        self::log($label, 'dump', ['data' => $var]);
    }
}
