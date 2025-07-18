<?php
declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Test\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\Config\Storage\WriterInterface;
use VelocityDev\DeveloperTools\Console\Command\EnableCommand;
use VelocityDev\DeveloperTools\Api\ProfilerConfigInterface;

/**
 * Test class for EnableCommand
 * @package VelocityDev\DeveloperTools\Test\Unit\Console\Command
 */
class EnableCommandTest extends TestCase
{
    /**
     * @var EnableCommand
     */
    private EnableCommand $enableCommand;

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
        
        $this->enableCommand = new EnableCommand(
            $this->configWriter
        );
    }

    /**
     * Test command configuration
     */
    public function testConfigure(): void
    {
        $this->assertEquals('profiler:enable', $this->enableCommand->getName());
        $this->assertEquals('Enable DB Profiler', $this->enableCommand->getDescription());
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
                '1'
            );

        $this->output->expects($this->once())
            ->method('writeln')
            ->with('<info>DB Profiler has been enabled.</info>');

        $result = $this->enableCommand->run($this->input, $this->output);
        
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
                '1'
            )
            ->willThrowException(new \Exception('Configuration save failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Configuration save failed');

        $this->enableCommand->run($this->input, $this->output);
    }
} 