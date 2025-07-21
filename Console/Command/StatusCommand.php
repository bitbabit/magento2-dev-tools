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
        $this->setName('profiler:status')
             ->setDescription('Show DB Profiler status and configuration');
    }
    
    /**
     * execute
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>DB Profiler Status</info>');
        $output->writeln('==================');
        $output->writeln('Enabled: ' . ($this->config->isEnabled() ? 'Yes' : 'No'));
        $output->writeln('Header Key: ' . $this->config->getProfilerHeaderKey());
        $output->writeln('HTML Output: ' . ($this->config->isHtmlOutputEnabled() ? 'Yes' : 'No'));
        $output->writeln('JSON Injection: ' . ($this->config->isJsonInjectionEnabled() ? 'Yes' : 'No'));
        $output->writeln('Log to File: ' . ($this->config->isLogToFileEnabled() ? 'Yes' : 'No'));
        $output->writeln('Developer Mode Only: ' . ($this->config->isDeveloperModeOnly() ? 'Yes' : 'No'));
        $output->writeln('Slow Query Threshold: ' . $this->config->getSlowQueryThreshold() . 'ms');
        $output->writeln('Toolbar Widget: ' . ($this->config->isToolbarWidgetEnabled() ? 'Yes' : 'No'));
        $output->writeln('Memory Limit: ' . $this->config->getMemoryLimitMb() . 'MB');
        $output->writeln('Current Memory Usage: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB');
        
        return Command::SUCCESS;
    }
}