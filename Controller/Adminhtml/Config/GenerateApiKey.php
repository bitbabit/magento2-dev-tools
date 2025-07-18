<?php
declare(strict_types=1);

namespace VelocityDev\DeveloperTools\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use VelocityDev\DeveloperTools\Api\ProfilerConfigInterface;

/**
 * Admin controller to generate API keys
 */
class GenerateApiKey extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'VelocityDev_DeveloperTools::developer_tools';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProfilerConfigInterface $profilerConfig
     * @param ConfigInterface $config
     */
    public function __construct(
        Context $context,
        private JsonFactory $resultJsonFactory,
        private ProfilerConfigInterface $profilerConfig,
        private ConfigInterface $config
    ) {
        parent::__construct($context);
    }

    /**
     * Generate API key action
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            // Generate new API key
            $newApiKey = $this->profilerConfig->generateApiKey();

            // Save to configuration
            $this->config->saveConfig(
                ProfilerConfigInterface::XML_PATH_API_KEY,
                $newApiKey,
                'default',
                0
            );

            // Enable API key validation
            $this->config->saveConfig(
                ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED,
                1,
                'default',
                0
            );

            return $resultJson->setData([
                'success' => true,
                'message' => 'API key generated successfully!',
                'api_key' => $newApiKey,
                'instructions' => [
                    'Configure your browser extension with this API key.',
                    'Save the configuration to apply changes.',
                    'Keep this key secure and don\'t share it publicly.'
                ]
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => 'Failed to generate API key: ' . $e->getMessage()
            ]);
        }
    }
} 