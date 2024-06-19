<?php

namespace Elegantly\Translator\Services\Translate;

interface TranslateServiceInterface
{
    public function translateAll(array $texts, string $targetLocale): array;

    public function translate(string $text, string $targetLocale): ?string;
}
