<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Test\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\Config\Storage\WriterInterface;
use BitBabit\DeveloperTools\Console\Command\DisableCommand;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;

/**
 * Test class for DisableCommand
 * @package BitBabit\DeveloperTools\Test\Unit\Console\Command
 */
class DisableCommandTest extends TestCase
{
    /**
     * @var DisableCommand
     */
    private DisableCommand $disableCommand;

    /**
     * @var WriterInterface|MockObject
     */
    private WriterInterface|MockObject $configWriter;

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
        $this->configWriter = $this->createMock(WriterInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        
        $this->disableCommand = new DisableCommand(
            $this->configWriter
        );
    }

    /**
     * Test command configuration
     */
    public function testConfigure(): void
    {
        $this->assertEquals('profiler:disable', $this->disableCommand->getName());
        $this->assertEquals('Disable DB Profiler', $this->disableCommand->getDescription());
    }

    /**
     * Test successful execution
     */
    public function testExecuteSuccess(): void
    {
        $this->configWriter->expects($this->once())
            ->method('save')
            ->with(
                ProfilerConfigInterface::XML_PATH_ENABLED,
                '0'
            );

        $this->output->expects($this->once())
            ->method('writeln')
            ->with('<info>DB Profiler has been disabled.</info>');

        $result = $this->disableCommand->run($this->input, $this->output);
        
        $this->assertEquals(Command::SUCCESS, $result);
    }

    /**
     * Test execution with exception
     */
    public function testExecuteWithException(): void
    {
        $this->configWriter->expects($this->once())
            ->method('save')
            ->with(
                ProfilerConfigInterface::XML_PATH_ENABLED,
                '0'
            )
            ->willThrowException(new \Exception('Configuration save failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Configuration save failed');

        $this->disableCommand->run($this->input, $this->output);
    }
} 