<?php
declare(strict_types=1);

namespace BitBabit\DeveloperTools\Api;

/**
 * SSR logs retrieval API for extension consumption.
 */
interface SsrLogRepositoryInterface
{
    /**
     * Get SSR logs by SSR ID.
     *
     * @param string $ssrId
     * @return array<string,mixed>
     */
    public function getBySsrId(string $ssrId): array;

    /**
     * Clear SSR logs by SSR ID.
     *
     * @param string $ssrId
     * @return bool
     */
    public function clearBySsrId(string $ssrId): bool;
}

