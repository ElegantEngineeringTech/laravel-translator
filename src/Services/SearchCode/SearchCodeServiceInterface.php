<?php

namespace Elegantly\Translator\Services\SearchCode;

interface SearchCodeServiceInterface
{
    public function translationsByFiles(): array;

    /**
     * @return array<string, array{ count: int, files: string[] }>
     */
    public function filesByTranslations(): array;
}
