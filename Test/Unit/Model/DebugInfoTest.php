<?php
declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Test\Unit\Model;

use VelocityDev\DeveloperTools\Model\DebugInfo;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for DebugInfo class
 */
class DebugInfoTest extends TestCase
{
    private DebugInfo $debugInfo;

    protected function setUp(): void
    {
        $this->debugInfo = DebugInfo::getInstance();
        $this->debugInfo->clear(); // Clear any existing messages
    }

    protected function tearDown(): void
    {
        $this->debugInfo->clear(); // Clean up after each test
    }

    /**
     * Test singleton pattern
     */
    public function testSingletonPattern(): void
    {
        $instance1 = DebugInfo::getInstance();
        $instance2 = DebugInfo::getInstance();
        
        $this->assertSame($instance1, $instance2, 'getInstance should return the same instance');
    }

    /**
     * Test adding a message with default parameters
     */
    public function testAddMessageWithDefaults(): void
    {
        $message = 'Test debug message';
        $this->debugInfo->addMessage($message);
        
        $messages = $this->debugInfo->getMessages();
        $this->assertCount(1, $messages, 'Should have one message');
        
        $addedMessage = $messages[0];
        $this->assertEquals($message, $addedMessage['message'], 'Message should match');
        $this->assertEquals('info', $addedMessage['level'], 'Default level should be info');
        $this->assertEquals([], $addedMessage['context'], 'Default context should be empty array');
        $this->assertIsFloat($addedMessage['timestamp'], 'Timestamp should be a float');
    }

    /**
     * Test adding a message with custom parameters
     */
    public function testAddMessageWithCustomParameters(): void
    {
        $message = 'Test debug message';
        $level = 'error';
        $context = ['key' => 'value', 'user_id' => 123];
        
        $this->debugInfo->addMessage($message, $level, $context);
        
        $messages = $this->debugInfo->getMessages();
        $this->assertCount(1, $messages, 'Should have one message');
        
        $addedMessage = $messages[0];
        $this->assertEquals($message, $addedMessage['message'], 'Message should match');
        $this->assertEquals($level, $addedMessage['level'], 'Level should match');
        $this->assertEquals($context, $addedMessage['context'], 'Context should match');
        $this->assertIsFloat($addedMessage['timestamp'], 'Timestamp should be a float');
    }

    /**
     * Test adding multiple messages
     */
    public function testAddMultipleMessages(): void
    {
        $this->debugInfo->addMessage('Message 1', 'info');
        $this->debugInfo->addMessage('Message 2', 'warning');
        $this->debugInfo->addMessage('Message 3', 'error');
        
        $messages = $this->debugInfo->getMessages();
        $this->assertCount(3, $messages, 'Should have three messages');
        
        $this->assertEquals('Message 1', $messages[0]['message']);
        $this->assertEquals('info', $messages[0]['level']);
        
        $this->assertEquals('Message 2', $messages[1]['message']);
        $this->assertEquals('warning', $messages[1]['level']);
        
        $this->assertEquals('Message 3', $messages[2]['message']);
        $this->assertEquals('error', $messages[2]['level']);
    }

    /**
     * Test clearing messages
     */
    public function testClearMessages(): void
    {
        $this->debugInfo->addMessage('Message 1');
        $this->debugInfo->addMessage('Message 2');
        
        $this->assertCount(2, $this->debugInfo->getMessages(), 'Should have two messages');
        
        $this->debugInfo->clear();
        
        $this->assertCount(0, $this->debugInfo->getMessages(), 'Should have no messages after clear');
    }

    /**
     * Test getMessagesAsJson
     */
    public function testGetMessagesAsJson(): void
    {
        $this->debugInfo->addMessage('Test message', 'info', ['key' => 'value']);
        
        $json = $this->debugInfo->getMessagesAsJson();
        $this->assertJson($json, 'Should return valid JSON');
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded, 'Decoded JSON should be an array');
        $this->assertCount(1, $decoded, 'Should have one message in JSON');
        $this->assertEquals('Test message', $decoded[0]['message']);
        $this->assertEquals('info', $decoded[0]['level']);
        $this->assertEquals(['key' => 'value'], $decoded[0]['context']);
    }

    /**
     * Test getData method
     */
    public function testGetData(): void
    {
        $this->debugInfo->addMessage('Test message 1');
        $this->debugInfo->addMessage('Test message 2');
        
        $data = $this->debugInfo->getData();
        
        $this->assertIsArray($data, 'getData should return an array');
        $this->assertArrayHasKey('messages', $data, 'Data should have messages key');
        $this->assertCount(2, $data['messages'], 'Should have two messages in data');
        $this->assertEquals($this->debugInfo->getMessages(), $data['messages'], 'Messages should match');
    }

    /**
     * Test that cloning is prevented
     */
    public function testCloneIsPrevented(): void
    {
        $this->expectException(\Error::class);
        $clone = clone $this->debugInfo;
    }

    /**
     * Test that unserialization throws exception
     */
    public function testUnserializationThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot unserialize singleton');
        
        $this->debugInfo->__wakeup();
    }

    /**
     * Test timestamp ordering
     */
    public function testTimestampOrdering(): void
    {
        $this->debugInfo->addMessage('First message');
        usleep(1000); // Sleep for 1ms to ensure different timestamps
        $this->debugInfo->addMessage('Second message');
        
        $messages = $this->debugInfo->getMessages();
        $this->assertCount(2, $messages);
        $this->assertLessThan($messages[1]['timestamp'], $messages[0]['timestamp'], 'First message should have earlier timestamp');
    }

    /**
     * Test empty messages array initially
     */
    public function testEmptyMessagesInitially(): void
    {
        $newDebugInfo = DebugInfo::getInstance();
        $newDebugInfo->clear();
        
        $this->assertCount(0, $newDebugInfo->getMessages(), 'Should have no messages initially');
        $this->assertEquals('[]', $newDebugInfo->getMessagesAsJson(), 'JSON should be empty array');
    }
} 