<?php

namespace Elegantly\Translator\Facades;

use Elegantly\Translator\Services\TranslatorServiceInterface;
use Elegantly\Translator\Translations;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, string> getLanguages()
 * @method static array<int, string> getNamespaces(string $locale)
 * @method static Translations getTranslations(string $locale, string $namespace)
 * @method static Translations sortTranslations(string $locale, string $namespace)
 * @method static Translations translateTranslations(string $referenceLocale, string $targetLocale, string $namespace, array $keys, ?TranslatorServiceInterface $service)
 * @method static array<int, string> getMissingTranslations(string $referenceLocale, string $targetLocale, string $namespace)
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
