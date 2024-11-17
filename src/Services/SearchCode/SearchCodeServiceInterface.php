<?php

namespace Elegantly\Translator\Services\SearchCode;

use Closure;
use Elegantly\Translator\Caches\SearchCodeCache;

interface SearchCodeServiceInterface
{
    public static function make(): self;

    public function getCache(): ?SearchCodeCache;

    /**
     * @param  null|(Closure(string $file, string[] $translations):void)  $progress
     * @return array<string, string[]>
     */
    public function translationsByFiles(?Closure $progress = null): array;

    /**
     * @param  null|(Closure(string $file, string[] $translations):void)  $progress
     * @return array<string, array{ count: int, files: string[] }>
     */
    public function filesByTranslations(?Closure $progress = null): array;
}
