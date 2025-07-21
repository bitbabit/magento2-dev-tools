<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Test\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use BitBabit\DeveloperTools\Console\Command\StatusCommand;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;

/**
 * Test class for StatusCommand
 * @package BitBabit\DeveloperTools\Test\Unit\Console\Command
 */
class StatusCommandTest extends TestCase
{
    /**
     * @var StatusCommand
     */
    private StatusCommand $statusCommand;

    /**
     * @var ProfilerConfigInterface|MockObject
     */
    private ProfilerConfigInterface|MockObject $config;

    /**
     * @var InputInterface|MockObject
     */
    private InputInterface|MockObject $input;

    /**
     * @var OutputInterface|MockObject
     */
    private OutputInterface|MockObject $output;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(ProfilerConfigInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        
        $this->statusCommand = new StatusCommand(
            $this->config
        );
    }

    /**
     * Test command configuration
     */
    public function testConfigure(): void
    {
        $this->assertEquals('profiler:status', $this->statusCommand->getName());
        $this->assertEquals('Show DB Profiler status and configuration', $this->statusCommand->getDescription());
    }

    /**
     * Test successful execution with all configs enabled
     */
    public function testExecuteSuccessAllEnabled(): void
    {
        // Mock config values
        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        
        $this->config->expects($this->once())
            ->method('getProfilerHeaderKey')
            ->willReturn('X-Debug-Profile');
        
        $this->config->expects($this->once())
            ->method('isHtmlOutputEnabled')
            ->willReturn(true);
        
        $this->config->expects($this->once())
            ->method('isJsonInjectionEnabled')
            ->willReturn(true);
        
        $this->config->expects($this->once())
            ->method('isLogToFileEnabled')
            ->willReturn(true);
        
        $this->config->expects($this->once())
            ->method('isDeveloperModeOnly')
            ->willReturn(true);
        
        $this->config->expects($this->once())
            ->method('getSlowQueryThreshold')
            ->willReturn(100);
        
        $this->config->expects($this->once())
            ->method('isToolbarWidgetEnabled')
            ->willReturn(true);
        
        $this->config->expects($this->once())
            ->method('getMemoryLimitMb')
            ->willReturn(128);

        // Expect output calls
        $this->output->expects($this->exactly(12))
            ->method('writeln')
            ->withConsecutive(
                ['<info>DB Profiler Status</info>'],
                ['=================='],
                ['Enabled: Yes'],
                ['Header Key: X-Debug-Profile'],
                ['HTML Output: Yes'],
                ['JSON Injection: Yes'],
                ['Log to File: Yes'],
                ['Developer Mode Only: Yes'],
                ['Slow Query Threshold: 100ms'],
                ['Toolbar Widget: Yes'],
                ['Memory Limit: 128MB'],
                [$this->stringContains('Current Memory Usage:')]
            );

        $result = $this->statusCommand->run($this->input, $this->output);
        
        $this->assertEquals(Command::SUCCESS, $result);
    }

    /**
     * Test successful execution with all configs disabled
     */
    public function testExecuteSuccessAllDisabled(): void
    {
        // Mock config values
        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        
        $this->config->expects($this->once())
            ->method('getProfilerHeaderKey')
            ->willReturn('X-Debug-Profile');
        
        $this->config->expects($this->once())
            ->method('isHtmlOutputEnabled')
            ->willReturn(false);
        
        $this->config->expects($this->once())
            ->method('isJsonInjectionEnabled')
            ->willReturn(false);
        
        $this->config->expects($this->once())
            ->method('isLogToFileEnabled')
            ->willReturn(false);
        
        $this->config->expects($this->once())
            ->method('isDeveloperModeOnly')
            ->willReturn(false);
        
        $this->config->expects($this->once())
            ->method('getSlowQueryThreshold')
            ->willReturn(50);
        
        $this->config->expects($this->once())
            ->method('isToolbarWidgetEnabled')
            ->willReturn(false);
        
        $this->config->expects($this->once())
            ->method('getMemoryLimitMb')
            ->willReturn(64);

        // Expect output calls
        $this->output->expects($this->exactly(12))
            ->method('writeln')
            ->withConsecutive(
                ['<info>DB Profiler Status</info>'],
                ['=================='],
                ['Enabled: No'],
                ['Header Key: X-Debug-Profile'],
                ['HTML Output: No'],
                ['JSON Injection: No'],
                ['Log to File: No'],
                ['Developer Mode Only: No'],
                ['Slow Query Threshold: 50ms'],
                ['Toolbar Widget: No'],
                ['Memory Limit: 64MB'],
                [$this->stringContains('Current Memory Usage:')]
            );

        $result = $this->statusCommand->run($this->input, $this->output);
        
        $this->assertEquals(Command::SUCCESS, $result);
    }
} 