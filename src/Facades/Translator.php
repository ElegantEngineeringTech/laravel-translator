<?php

namespace Elegantly\Translator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getLanguages()
 * @method static array getNamespaces(string $locale)
 * @method static Translations sortTranslations(string $locale, string $namespace)
 * @method static array getMissingTranslations(string $referenceLocale, string $targetLocale, string $namespace)
 * @method static array getAllMissingTranslations(string $referenceLocale)
 * @method static void sortAllTranslations()
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
