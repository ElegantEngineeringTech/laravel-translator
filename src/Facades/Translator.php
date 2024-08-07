<?php

namespace Elegantly\Translator\Facades;

use Closure;
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
 * @method static array getAllDeadTranslations( null|(Closure(string $file, string[] $translations):void) $progress = null )
 * @method static array getDeadTranslations(string $locale, string $namespace, ?SearchCodeServiceInterface $service = null, null|(Closure(string $file, string[] $translations):void) $progress = null, ?array $ignore = null )
 * @method static void sortAllTranslations()
 * @method static \Elegantly\Translator\Translator clearCache()
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
