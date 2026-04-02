<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Model;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * DI-managed service for collecting debug messages.
 *
 * Implements ResetAfterRequestInterface so the Application Server (Swoole)
 * automatically clears per-request messages after each response.
 *
 * @package BitBabit\DeveloperTools\Model
 */
class DebugInfo implements ResetAfterRequestInterface
{
    private array $messages = [];

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

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->messages = [];
    }
}
