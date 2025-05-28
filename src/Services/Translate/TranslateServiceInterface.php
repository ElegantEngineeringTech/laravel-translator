<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Translate;

interface TranslateServiceInterface
{
    /**
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public function translateAll(array $texts, string $targetLocale): array;

    public static function make(): self;
}
