<?php

namespace Elegantly\Translator\Services\Translate;

interface TranslateServiceInterface
{
    /**
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    public function translateAll(array $texts, string $targetLocale): array;
}
