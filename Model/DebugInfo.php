<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Model;

/**
 * DebugInfo singleton class for collecting debug messages
 * @package BitBabit\DeveloperTools\Model
 */
class DebugInfo
{
    private static ?DebugInfo $instance = null;
    private array $messages = [];


    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add a debug message
     * @param string $message
     * @param string $level
     * @param array $context
     */
    public function addMessage(string $message, string $level = 'info', array $context = []): void
    {
        $this->messages[] = [
            'message' => $message,
            'level' => $level,
            'context' => $context,
            'timestamp' => microtime(true)
        ];
    }



    /**
     * Get all debug messages
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }



    /**
     * Clear all messages
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Get messages as JSON
     * @return string
     */
    public function getMessagesAsJson(): string
    {
        return json_encode($this->messages, JSON_PRETTY_PRINT);
    }

    /**
     * Get debug data for both browser extension and profiler widget
     * @return array
     */
    public function getData(): array
    {
        return [
            'messages' => $this->getMessages()
        ];
    }



    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
} 