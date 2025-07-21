<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;

/**
 * EnableCommand
 * @package BitBabit\DeveloperTools\Console\Command
 */
class EnableCommand extends Command
{
    /**
     * EnableCommand constructor
     * @param WriterInterface $configWriter
     * @param string|null $name
     */
    public function __construct(
        private readonly WriterInterface $configWriter,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * configure
    */
    protected function configure(): void
    {
        $this->setName('bitbabit:profiler:enable')
             ->setDescription('Enable BitBabit Developer Tools profiler with default configuration');
    }

    /**
     * execute
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Enable the main profiler
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_ENABLED, '1');
        
        // Set Profiler Header Key to X-Debug-Mode
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_HEADER_KEY, 'X-Debug-Mode');
        
        // Enable HTML Output for Web Requests
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_HTML_OUTPUT, '1');
        
        // Enable JSON Injection for API Responses
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_JSON_INJECTION, '1');
        
        // Disable Restrict to Developer Mode Only
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_DEVELOPER_MODE_ONLY, '0');
        
        // Set Highlight Queries Slower Than to 0.001ms
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_SLOW_QUERY_THRESHOLD, '0.001');
        
        // Enable Profiler Toolbar Widget
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_TOOLBAR_WIDGET, '1');
        
        // Enable API Key Validation
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED, '1');

        $output->writeln('<info>DB Profiler has been enabled with default configuration:</info>');
        $output->writeln('<info>- Profiler Header Key: X-Debug-Mode</info>');
        $output->writeln('<info>- HTML Output: Enabled</info>');
        $output->writeln('<info>- JSON Injection: Enabled</info>');
        $output->writeln('<info>- Developer Mode Only: Disabled</info>');
        $output->writeln('<info>- Slow Query Threshold: 0.001ms</info>');
        $output->writeln('<info>- Toolbar Widget: Enabled</info>');
        $output->writeln('<info>- API Key Validation: Enabled</info>');

        return Command::SUCCESS;
    }
}