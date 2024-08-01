<?php

namespace Elegantly\Translator\Facades;

use Elegantly\Translator\Services\Grammar\GrammarServiceInterface;
use Elegantly\Translator\Services\Translate\TranslateServiceInterface;
use Elegantly\Translator\Translations;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, string> getLocales()
 * @method static array<int, string> getNamespaces(string $locale)
 * @method static Translations getTranslations(string $locale, string $namespace)
 * @method static Translations sortTranslations(string $locale, string $namespace)
 * @method static Translations translateTranslations(string $referenceLocale, string $targetLocale, string $namespace, array $keys, ?TranslateServiceInterface $service)
 * @method static Translations fixGrammarTranslations(string $locale, string $namespace, array $keys, ?GrammarServiceInterface $service)
 * @method static array<int, string> getMissingTranslations(string $referenceLocale, string $targetLocale, string $namespace)
 * @method static array getAllMissingTranslations(string $referenceLocale)
 * @method static array getAllDeadTranslations()
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
