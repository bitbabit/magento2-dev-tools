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
        $this->setName('profiler:enable')
             ->setDescription('Enable DB Profiler');
    }

    /**
     * execute
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configWriter->save(ProfilerConfigInterface::XML_PATH_ENABLED, '1');
        $output->writeln('<info>DB Profiler has been enabled.</info>');

        return Command::SUCCESS;
    }
}