<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;

/**
 * StatusCommand
 * @package BitBabit\DeveloperTools\Console\Command
 */
class StatusCommand extends Command
{
    /**
     * StatusCommand constructor
     * @param ProfilerConfigInterface $config
     * @param string|null $name
     */
    public function __construct(
        private ProfilerConfigInterface $config,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * configure
     */
    protected function configure(): void
    {
        $this->setName('bitbabit:devtools:status')
             ->setDescription('Show BitBabit Developer Tools profiler status and configuration');
    }
    
    /**
     * execute
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>BitBabit Developer Tools Status</info>');
        $output->writeln('================================');
        $output->writeln('');
        $output->writeln('<comment>General:</comment>');
        $output->writeln('  Enabled:              ' . ($this->config->isEnabled() ? '<info>Yes</info>' : '<error>No</error>'));
        $output->writeln('  Header Key:           ' . $this->config->getProfilerHeaderKey());
        $output->writeln('  Developer Mode Only:  ' . ($this->config->isDeveloperModeOnly() ? 'Yes' : 'No'));
        $output->writeln('');
        $output->writeln('<comment>Output:</comment>');
        $output->writeln('  HTML Output:          ' . ($this->config->isHtmlOutputEnabled() ? 'Yes' : 'No'));
        $output->writeln('  JSON Injection:       ' . ($this->config->isJsonInjectionEnabled() ? 'Yes' : 'No'));
        $output->writeln('  Log to File:          ' . ($this->config->isLogToFileEnabled() ? 'Yes' : 'No'));
        $output->writeln('  Toolbar Widget:       ' . ($this->config->isToolbarWidgetEnabled() ? 'Yes' : 'No'));
        $output->writeln('');
        $output->writeln('<comment>Thresholds:</comment>');
        $output->writeln('  Slow Query Threshold: ' . $this->config->getSlowQueryThreshold() . 'ms');
        $output->writeln('  Memory Limit:         ' . $this->config->getMemoryLimitMb() . 'MB');
        $output->writeln('  Current Memory Usage: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB');
        $output->writeln('');
        $output->writeln('<comment>API Security:</comment>');
        $output->writeln('  API Key Validation:   ' . ($this->config->isApiKeyEnabled() ? '<info>Enabled</info>' : '<error>Disabled</error>'));
        $output->writeln('  API Key Configured:   ' . ($this->config->getApiKey() ? '<info>Yes</info>' : '<error>No</error>'));
        
        return Command::SUCCESS;
    }
}