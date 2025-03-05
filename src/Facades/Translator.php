<?php

declare(strict_types=1);

namespace Elegantly\Translator\Facades;

use Elegantly\Translator\Collections\Translations;
use Elegantly\Translator\Drivers\Driver;
use Elegantly\Translator\Services\Exporter\ExporterInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Elegantly\Translator\Translator driver(null|string|Driver $name)
 * @method static array<int, string> getLocales()
 * @method static Translations getTranslations(string $locale)
 * @method static array<string, array{ count: int, files: string[] }> getMissingTranslations(string $locale)
 * @method static array<int, scalar|null> getDeadTranslations(string $locale)
 * @method static Translations getUntranslatedTranslations(string $source, string $target)
 * @method static Translations setTranslations(string $locale, array<string, scalar|null> $values)
 * @method static Translations translateTranslations(string $source, string $target, array<int, string> $keys)
 * @method static Translations proofreadTranslations(string $locale, array<int, string> $keys)
 * @method static Translations deleteTranslations(string $locale, array<int, string> $keys)
 * @method static Translations sortTranslations(string $locale)
 * @method static Translations saveTranslations(string $locale, Translations $translations)
 * @method static string exportTranslations(string $path, ?ExporterInterface $exporter = null)
 * @method static array<string, array<int|string, scalar>> importTranslations(string $path, ?ExporterInterface $exporter = null)
 * @method static void clearCache()
 *
 * @see \Elegantly\Translator\Translator
 */
class Translator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Elegantly\Translator\Translator::class;
    }
}
