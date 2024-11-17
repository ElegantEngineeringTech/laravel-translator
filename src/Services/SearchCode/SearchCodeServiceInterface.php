<?php

namespace Elegantly\Translator\Services\SearchCode;

use Elegantly\Translator\Caches\SearchCodeCache;

interface SearchCodeServiceInterface
{
    public static function make(): self;

    public function getCache(): ?SearchCodeCache;

    /**
     * @return array<string, string[]>
     */
    public function translationsByFiles(): array;

    /**
     * @return array<string, array{ count: int, files: string[] }>
     */
    public function filesByTranslations(): array;
}
