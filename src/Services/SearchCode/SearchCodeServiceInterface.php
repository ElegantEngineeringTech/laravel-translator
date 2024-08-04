<?php

namespace Elegantly\Translator\Services\SearchCode;

use Closure;

interface SearchCodeServiceInterface
{
    /**
     * @return array<string, string[]>
     */
    public function translationsByFiles(?Closure $progress = null): array;

    /**
     * @return array<string, array{ count: int, files: string[] }>
     */
    public function filesByTranslations(?Closure $progress = null): array;
}
