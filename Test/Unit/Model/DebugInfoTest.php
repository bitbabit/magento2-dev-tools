<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Test\Unit\Model;

use BitBabit\DeveloperTools\Model\DebugInfo;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for DebugInfo class
 */
class DebugInfoTest extends TestCase
{
    private DebugInfo $debugInfo;

    protected function setUp(): void
    {
        $this->debugInfo = new DebugInfo();
    }

    public function testImplementsResetAfterRequestInterface(): void
    {
        $this->assertInstanceOf(
            ResetAfterRequestInterface::class,
            $this->debugInfo,
            'DebugInfo must implement ResetAfterRequestInterface for Application Server compatibility'
        );
    }

    public function testAddMessageWithDefaults(): void
    {
        $message = 'Test debug message';
        $this->debugInfo->addMessage($message);

        $messages = $this->debugInfo->getMessages();
        $this->assertCount(1, $messages);

        $addedMessage = $messages[0];
        $this->assertEquals($message, $addedMessage['message']);
        $this->assertEquals('info', $addedMessage['level']);
        $this->assertEquals([], $addedMessage['context']);
        $this->assertIsFloat($addedMessage['timestamp']);
    }

    public function testAddMessageWithCustomParameters(): void
    {
        $message = 'Test debug message';
        $level = 'error';
        $context = ['key' => 'value', 'user_id' => 123];

        $this->debugInfo->addMessage($message, $level, $context);

        $messages = $this->debugInfo->getMessages();
        $this->assertCount(1, $messages);

        $addedMessage = $messages[0];
        $this->assertEquals($message, $addedMessage['message']);
        $this->assertEquals($level, $addedMessage['level']);
        $this->assertEquals($context, $addedMessage['context']);
        $this->assertIsFloat($addedMessage['timestamp']);
    }

    public function testAddMultipleMessages(): void
    {
        $this->debugInfo->addMessage('Message 1', 'info');
        $this->debugInfo->addMessage('Message 2', 'warning');
        $this->debugInfo->addMessage('Message 3', 'error');

        $messages = $this->debugInfo->getMessages();
        $this->assertCount(3, $messages);

        $this->assertEquals('Message 1', $messages[0]['message']);
        $this->assertEquals('info', $messages[0]['level']);

        $this->assertEquals('Message 2', $messages[1]['message']);
        $this->assertEquals('warning', $messages[1]['level']);

        $this->assertEquals('Message 3', $messages[2]['message']);
        $this->assertEquals('error', $messages[2]['level']);
    }

    public function testClearMessages(): void
    {
        $this->debugInfo->addMessage('Message 1');
        $this->debugInfo->addMessage('Message 2');

        $this->assertCount(2, $this->debugInfo->getMessages());

        $this->debugInfo->clear();

        $this->assertCount(0, $this->debugInfo->getMessages());
    }

    public function testGetMessagesAsJson(): void
    {
        $this->debugInfo->addMessage('Test message', 'info', ['key' => 'value']);

        $json = $this->debugInfo->getMessagesAsJson();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals('Test message', $decoded[0]['message']);
        $this->assertEquals('info', $decoded[0]['level']);
        $this->assertEquals(['key' => 'value'], $decoded[0]['context']);
    }

    public function testGetData(): void
    {
        $this->debugInfo->addMessage('Test message 1');
        $this->debugInfo->addMessage('Test message 2');

        $data = $this->debugInfo->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertCount(2, $data['messages']);
        $this->assertEquals($this->debugInfo->getMessages(), $data['messages']);
    }

    public function testTimestampOrdering(): void
    {
        $this->debugInfo->addMessage('First message');
        usleep(1000);
        $this->debugInfo->addMessage('Second message');

        $messages = $this->debugInfo->getMessages();
        $this->assertCount(2, $messages);
        $this->assertLessThan($messages[1]['timestamp'], $messages[0]['timestamp']);
    }

    public function testEmptyMessagesInitially(): void
    {
        $this->assertCount(0, $this->debugInfo->getMessages());
        $this->assertEquals('[]', $this->debugInfo->getMessagesAsJson());
    }

    /**
     * Verify _resetState() returns the object to its post-construction state,
     * matching the contract required by GraphQL Application Server.
     */
    public function testResetStateClearsMessages(): void
    {
        $this->debugInfo->addMessage('Message 1');
        $this->debugInfo->addMessage('Message 2');
        $this->assertCount(2, $this->debugInfo->getMessages());

        $this->debugInfo->_resetState();

        $this->assertCount(0, $this->debugInfo->getMessages(), '_resetState must clear all messages');
    }

    public function testResetStateMatchesConstructionState(): void
    {
        $freshInstance = new DebugInfo();

        $this->debugInfo->addMessage('some data');
        $this->debugInfo->_resetState();

        $this->assertEquals(
            $freshInstance->getMessages(),
            $this->debugInfo->getMessages(),
            'After _resetState, object state must match a freshly constructed instance'
        );
    }
}
