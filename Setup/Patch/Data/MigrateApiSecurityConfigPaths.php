<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Setup\Patch\Data;

use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Moves legacy admin paths bitbabit/api_security/* to bitbabit/dev_tools/* (used by ProfilerConfig).
 */
class MigrateApiSecurityConfigPaths implements DataPatchInterface
{
    private const LEGACY_PATHS = [
        'bitbabit/api_security/api_key' => ProfilerConfigInterface::XML_PATH_API_KEY,
        'bitbabit/api_security/api_key_enabled' => ProfilerConfigInterface::XML_PATH_API_KEY_ENABLED,
    ];

    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private WriterInterface $configWriter,
        private EncryptorInterface $encryptor
    ) {
    }

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        foreach (self::LEGACY_PATHS as $legacyPath => $targetPath) {
            $legacyRows = $connection->fetchAll(
                $connection->select()
                    ->from($table, ['config_id', 'scope', 'scope_id', 'value'])
                    ->where('path = ?', $legacyPath)
            );

            foreach ($legacyRows as $legacyRow) {
                $scope = (string) $legacyRow['scope'];
                $scopeId = (int) $legacyRow['scope_id'];
                $legacyValue = (string) ($legacyRow['value'] ?? '');

                if ($legacyValue === '') {
                    continue;
                }

                $targetValue = $this->normalizeConfigValue($legacyPath, $legacyValue);
                $existingTarget = $connection->fetchOne(
                    $connection->select()
                        ->from($table, ['value'])
                        ->where('path = ?', $targetPath)
                        ->where('scope = ?', $scope)
                        ->where('scope_id = ?', $scopeId)
                );

                if ($existingTarget === false || $existingTarget === null || $existingTarget === '') {
                    $this->configWriter->save($targetPath, $targetValue, $scope, $scopeId);
                }
            }

            $connection->delete($table, ['path = ?' => $legacyPath]);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    private function normalizeConfigValue(string $legacyPath, string $value): string
    {
        if ($legacyPath !== 'bitbabit/api_security/api_key') {
            return $value;
        }

        if (str_starts_with($value, '0:')) {
            try {
                return (string) $this->encryptor->decrypt($value);
            } catch (\Exception) {
                return $value;
            }
        }

        return $value;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
