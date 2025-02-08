<?php

declare(strict_types=1);

namespace Elegantly\Translator\Drivers;

use Elegantly\Translator\Collections\Translations;

abstract class Driver
{
    /**
     * Make an instance based on configs
     *
     * @param  array<array-key, mixed>  $config
     */
    abstract public static function make(array $config = []): static;

    /**
     * A unique identifier for the driver
     */
    abstract public function getKey(): string;

    /**
     * @return static[]
     */
    abstract public function getSubDrivers(): array;

    /**
     * @return array<int, string>
     */
    abstract public function getLocales(): array;

    abstract public function getTranslations(string $locale): Translations;

    abstract public function saveTranslations(string $locale, Translations $translations): Translations;

    abstract public static function collect(): Translations;
}
