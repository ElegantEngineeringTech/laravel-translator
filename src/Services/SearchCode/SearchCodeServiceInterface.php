<?php

namespace Elegantly\Translator\Services\SearchCode;

interface SearchCodeServiceInterface
{
    /**
     * @return array<string, string[]>
     */
    public function translationsByFiles(): array;

    /**
     * @return array<string, array{ count: int, files: string[] }>
     */
    public function filesByTranslations(): array;
}
