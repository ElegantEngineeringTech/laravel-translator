<?php

namespace Elegantly\Translator\Drivers;

use Elegantly\Translator\Collections\Translations;

abstract class Driver
{
    /**
     * Make an instance based on configs
     */
    abstract public static function make(): static;

    /**
     * @return array<int, string>
     */
    abstract public function getLocales(): array;

    abstract public function getTranslations(string $locale): Translations;

    abstract public function saveTranslations(string $locale, Translations $translations): Translations;

    abstract public static function collect(): Translations;
}
