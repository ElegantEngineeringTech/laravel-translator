<?php

namespace Elegantly\Translator\Drivers;

use Elegantly\Translator\Collections\TranslationsInterface;

abstract class Driver
{
    abstract public static function make(): static;

    /**
     * @return string[]
     */
    abstract public function getLocales(): array;

    /**
     * @return string[]
     */
    abstract public function getNamespaces(string $locale): array;

    abstract public function getTranslations(string $locale, ?string $namespace = null): TranslationsInterface;
}
