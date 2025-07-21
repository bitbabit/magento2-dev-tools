<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Test\Unit\Console;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use BitBabit\DeveloperTools\Console\GenerateApiKeyCommand;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;

/**
 * Test class for GenerateApiKeyCommand
 * @package BitBabit\DeveloperTools\Test\Unit\Console
 */
class GenerateApiKeyCommandTest extends TestCase
{
    /**
     * @var GenerateApiKeyCommand
     */
    private GenerateApiKeyCommand $generateApiKeyCommand;

    /**
     * @var ProfilerConfigInterface|MockObject
     */
    private ProfilerConfigInterface|MockObject $profilerConfig;

    /**
     * @var ConfigInterface|MockObject
     */
    private ConfigInterface|MockObject $config;

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
        $this->profilerConfig = $this->createMock(ProfilerConfigInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        
        $this->generateApiKeyCommand = new GenerateApiKeyCommand(
            $this->profilerConfig,
            $this->config
        );
    }

    /**
     * Test command configuration
     */
    public function testConfigure(): void
    {
        $this->assertEquals('bitbabit:devtools:generate-api-key', $this->generateApiKeyCommand->getName());
        $this->assertEquals('Generate API key for Developer Tools', $this->generateApiKeyCommand->getDescription());
    }

    /**
     * Test execution when API key already exists and regenerate is not requested
     */
    public function testExecuteApiKeyExistsNoRegenerate(): void
    {
        $existingKey = 'existing-api-key-123';
        
        $this->profilerConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn($existingKey);
        
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('regenerate')
            ->willReturn(false);

        $this->output->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['<comment>API key already exists. Use --regenerate to create a new one.</comment>'],
                ['<info>Current API key: ' . $existingKey . '</info>']
            );

        $result = $this->generateApiKeyCommand->run($this->input, $this->output);
        
        $this->assertEquals(Cli::RETURN_SUCCESS, $result);
    }

    /**
     * Test execution when API key doesn't exist
     */
    public function testExecuteNoExistingApiKey(): void
    {
        $newApiKey = 'new-api-key-456';
        
        $this->profilerConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn(null);
        
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('regenerate')
            ->willReturn(false);

        $this->profilerConfig->expects($this->once())
            ->method('generateApiKey')
            ->willReturn($newApiKey);

        $this->config->expects($this->exactly(2))
            ->method('saveConfig')
            ->withConsecutive(
                [
                    ProfilerConfigInterface::XML_PATH_API_KEY,
                    $newApiKey,
                    'default',
                    0
                ],
                [
                    ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED,
                    1,
                    'default',
                    0
                ]
            );

        $this->output->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                ['<success>✅ API key generated successfully!</success>'],
                ['<info>New API key: ' . $newApiKey . '</info>'],
                ['<comment>⚠️  Save this key securely! You\'ll need it for your browser extension.</comment>'],
                ['<comment>💡 Configure your Chrome extension with this API key to enable profiling.</comment>']
            );

        $result = $this->generateApiKeyCommand->run($this->input, $this->output);
        
        $this->assertEquals(Cli::RETURN_SUCCESS, $result);
    }

    /**
     * Test execution when regenerate is requested
     */
    public function testExecuteRegenerate(): void
    {
        $existingKey = 'existing-api-key-123';
        $newApiKey = 'new-api-key-456';
        
        $this->profilerConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn($existingKey);
        
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('regenerate')
            ->willReturn(true);

        $this->profilerConfig->expects($this->once())
            ->method('generateApiKey')
            ->willReturn($newApiKey);

        $this->config->expects($this->exactly(2))
            ->method('saveConfig')
            ->withConsecutive(
                [
                    ProfilerConfigInterface::XML_PATH_API_KEY,
                    $newApiKey,
                    'default',
                    0
                ],
                [
                    ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED,
                    1,
                    'default',
                    0
                ]
            );

        $this->output->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                ['<success>✅ API key regenerated successfully!</success>'],
                ['<info>New API key: ' . $newApiKey . '</info>'],
                ['<comment>⚠️  Save this key securely! You\'ll need it for your browser extension.</comment>'],
                ['<comment>💡 Configure your Chrome extension with this API key to enable profiling.</comment>']
            );

        $result = $this->generateApiKeyCommand->run($this->input, $this->output);
        
        $this->assertEquals(Cli::RETURN_SUCCESS, $result);
    }

    /**
     * Test execution with exception
     */
    public function testExecuteWithException(): void
    {
        $this->profilerConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn(null);
        
        $this->input->expects($this->once())
            ->method('getOption')
            ->with('regenerate')
            ->willReturn(false);

        $this->profilerConfig->expects($this->once())
            ->method('generateApiKey')
            ->willThrowException(new \Exception('Key generation failed'));

        $this->output->expects($this->once())
            ->method('writeln')
            ->with('<error>❌ Failed to generate API key: Key generation failed</error>');

        $result = $this->generateApiKeyCommand->run($this->input, $this->output);
        
        $this->assertEquals(Cli::RETURN_FAILURE, $result);
    }
} 