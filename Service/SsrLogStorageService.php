<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Service;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Stores SSR profiler logs keyed by X-SSR-ID.
 *
 * Uses Magento cache backend (Redis/file/etc.) with TTL so data
 * is short-lived and safe for development diagnostics.
 */
class SsrLogStorageService
{
    private const CACHE_KEY_PREFIX = 'devtools_ssr_';
    private const CACHE_TAG = 'DEVTOOLS_SSR';
    private const DEFAULT_TTL_SECONDS = 900; // 15 minutes
    private const MAX_ENTRIES_PER_SSR_ID = 50;

    public function __construct(
        private CacheInterface $cache,
        private Json $jsonSerializer
    ) {
    }

    /**
     * Append one profiler payload for a given SSR ID.
     *
     * @param string $ssrId
     * @param array $entry
     * @return void
     */
    public function append(string $ssrId, array $entry): void
    {
        $cacheKey = $this->buildCacheKey($ssrId);
        $existing = $this->get($ssrId);

        $existing['ssr_id'] = $ssrId;
        $existing['updated_at'] = gmdate('c');
        $existing['entries'] = $existing['entries'] ?? [];
        $existing['entries'][] = $entry;

        if (count($existing['entries']) > self::MAX_ENTRIES_PER_SSR_ID) {
            $existing['entries'] = array_slice($existing['entries'], -self::MAX_ENTRIES_PER_SSR_ID);
        }

        $this->cache->save(
            $this->jsonSerializer->serialize($existing),
            $cacheKey,
            [self::CACHE_TAG],
            self::DEFAULT_TTL_SECONDS
        );
    }

    /**
     * @param string $ssrId
     * @return array<string,mixed>
     */
    public function get(string $ssrId): array
    {
        $raw = $this->cache->load($this->buildCacheKey($ssrId));
        if (!$raw) {
            return [
                'ssr_id' => $ssrId,
                'updated_at' => null,
                'entries' => [],
            ];
        }

        try {
            $data = $this->jsonSerializer->unserialize($raw);
            if (is_array($data)) {
                return $data;
            }
        } catch (\InvalidArgumentException $e) {
            // Return empty structure for malformed cache payloads.
        }

        return [
            'ssr_id' => $ssrId,
            'updated_at' => null,
            'entries' => [],
        ];
    }

    /**
     * Remove stored logs for an SSR ID.
     *
     * @param string $ssrId
     * @return bool
     */
    public function clear(string $ssrId): bool
    {
        return $this->cache->remove($this->buildCacheKey($ssrId));
    }

    /**
     * Simple SSR ID validation to prevent cache-key abuse.
     */
    public function isValidSsrId(string $ssrId): bool
    {
        if ($ssrId === '' || strlen($ssrId) > 128) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9._:-]+$/', $ssrId);
    }

    private function buildCacheKey(string $ssrId): string
    {
        return self::CACHE_KEY_PREFIX . hash('sha256', $ssrId);
    }
}

