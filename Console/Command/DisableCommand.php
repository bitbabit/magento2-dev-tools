<?php
declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use VelocityDev\DeveloperTools\Api\ProfilerConfigInterface;

/**
 * DisableCommand
 * @package VelocityDev\DeveloperTools\Console\Command
 */
class DisableCommand extends Command
{
    /**
     * DisableCommand constructor
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
        $this->setName('profiler:disable')
             ->setDescription('Disable DB Profiler');
    }

    /**
     * execute
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_ENABLED, '0');
        $output->writeln('<info>DB Profiler has been disabled.</info>');
        return Command::SUCCESS;
    }
}
