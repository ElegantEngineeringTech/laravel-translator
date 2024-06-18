<?php

namespace Elegantly\Translator\Services;

interface TranslatorServiceInterface
{
    public function translateAll(array $texts, string $targetLocale): array;

    public function translate(string $text, string $targetLocale): ?string;
}
