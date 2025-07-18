<?php
declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Console;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Console\Cli;
use VelocityDev\DeveloperTools\Api\ProfilerConfigInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command to generate API key for developer tools
 */
class GenerateApiKeyCommand extends Command
{
    private const OPTION_REGENERATE = 'regenerate';

    /**
     * @param ProfilerConfigInterface $profilerConfig
     * @param ConfigInterface $config
     */
    public function __construct(
        private ProfilerConfigInterface $profilerConfig,
        private ConfigInterface $config
    ) {
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('velocity:devtools:generate-api-key')
            ->setDescription('Generate API key for Developer Tools')
            ->addOption(
                self::OPTION_REGENERATE,
                'r',
                InputOption::VALUE_NONE,
                'Regenerate API key even if one already exists'
            );
    }

    /**
     * Execute command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $existingKey = $this->profilerConfig->getApiKey();
            $regenerate = $input->getOption(self::OPTION_REGENERATE);

            if ($existingKey && !$regenerate) {
                $output->writeln('<comment>API key already exists. Use --regenerate to create a new one.</comment>');
                $output->writeln('<info>Current API key: ' . $existingKey . '</info>');
                return Cli::RETURN_SUCCESS;
            }

            // Generate new API key
            $newApiKey = $this->profilerConfig->generateApiKey();

            // Save to configuration
            $this->config->saveConfig(
                ProfilerConfigInterface::XML_PATH_API_KEY,
                $newApiKey,
                'default',
                0
            );

            // Enable API key validation by default when generating key
            $this->config->saveConfig(
                ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED,
                1,
                'default',
                0
            );

            if ($existingKey) {
                $output->writeln('<success>✅ API key regenerated successfully!</success>');
            } else {
                $output->writeln('<success>✅ API key generated successfully!</success>');
            }

            $output->writeln('<info>New API key: ' . $newApiKey . '</info>');
            $output->writeln('<comment>⚠️  Save this key securely! You\'ll need it for your browser extension.</comment>');
            $output->writeln('<comment>💡 Configure your Chrome extension with this API key to enable profiling.</comment>');

            return Cli::RETURN_SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>❌ Failed to generate API key: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
} 