<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\SearchCode;

use Closure;
use Elegantly\Translator\Caches\SearchCodeCache;

interface SearchCodeServiceInterface
{
    public static function make(): self;

    public function getCache(): ?SearchCodeCache;

    /**
     * @param  null|(Closure(string $path):void)  $progress
     * @param  null|(Closure(int $total):void)  $start
     * @param  null|(Closure():void)  $end
     * @return array<string, string[]>
     */
    public function translationsByFiles(
        ?Closure $progress = null,
        ?Closure $start = null,
        ?Closure $end = null,
    ): array;

    /**
     * @param  null|(Closure(string $path):void)  $progress
     * @param  null|(Closure(int $total):void)  $start
     * @param  null|(Closure():void)  $end
     * @return array<string, array{ count: int, files: string[] }>
     */
    public function filesByTranslations(
        ?Closure $progress = null,
        ?Closure $start = null,
        ?Closure $end = null,
    ): array;
}
