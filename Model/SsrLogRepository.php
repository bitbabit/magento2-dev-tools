<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Model;

use BitBabit\DeveloperTools\Api\ProfilerConfigInterface;
use BitBabit\DeveloperTools\Api\SsrLogRepositoryInterface;
use BitBabit\DeveloperTools\Service\SsrLogStorageService;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\HTTP\PhpEnvironment\Request;

/**
 * SSR logs repository implementation.
 */
class SsrLogRepository implements SsrLogRepositoryInterface
{
    public function __construct(
        private ProfilerConfigInterface $config,
        private SsrLogStorageService $ssrLogStorage,
        private Request $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getBySsrId(string $ssrId): array
    {
        if (!$this->config->isEnabled()) {
            throw new AuthenticationException(__('Developer tools is disabled.'));
        }

        if (!$this->config->validateApiKey($this->request)) {
            throw new AuthenticationException(__('Invalid or missing debug API key.'));
        }

        if (!$this->ssrLogStorage->isValidSsrId($ssrId)) {
            throw new InputException(__('Invalid SSR ID format.'));
        }

        $result = $this->ssrLogStorage->get($ssrId);

        return [
            'ssr_id' => $result['ssr_id'] ?? $ssrId,
            'updated_at' => $result['updated_at'] ?? null,
            'count' => isset($result['entries']) && is_array($result['entries']) ? count($result['entries']) : 0,
            'entries' => $result['entries'] ?? [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function clearBySsrId(string $ssrId): bool
    {
        if (!$this->config->isEnabled()) {
            throw new AuthenticationException(__('Developer tools is disabled.'));
        }

        if (!$this->config->validateApiKey($this->request)) {
            throw new AuthenticationException(__('Invalid or missing debug API key.'));
        }

        if (!$this->ssrLogStorage->isValidSsrId($ssrId)) {
            throw new InputException(__('Invalid SSR ID format.'));
        }

        return $this->ssrLogStorage->clear($ssrId);
    }
}

