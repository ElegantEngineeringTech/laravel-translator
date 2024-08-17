<?php

namespace Elegantly\Translator\Facades;

use Closure;
use Elegantly\Translator\Collections\JsonTranslations;
use Elegantly\Translator\Collections\PhpTranslations;
use Elegantly\Translator\Services\Proofread\ProofreadServiceInterface;
use Elegantly\Translator\Services\SearchCode\SearchCodeServiceInterface;
use Elegantly\Translator\Services\Translate\TranslateServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ?TranslateServiceInterface getTranslateService()
 * @method static ?ProofreadServiceInterface getProofreadService()
 * @method static ?SearchCodeServiceInterface getSearchcodeService()
 * @method static string getJsonNamespace()
 * @method static array<int, string> getLocales()
 * @method static array<int, string> getNamespaces(string $locale)
 * @method static PhpTranslations|JsonTranslations getTranslations(string $locale, ?string $namespace)
 * @method static Collection<int, string> getMissingTranslations(string $source, string $target, ?string $namespace)
 * @method static Collection<string, Collection<string, Collection<int, string>>> getAllMissingTranslations(string $source)
 * @method static Collection<int, string> getDeadTranslations(string $locale, ?string $namespace, ?SearchCodeServiceInterface $service = null, ?Closure $progress = null, ?array $ignore = null)
 * @method static Collection<string, Collection<string, Collection<int, string>>> getAllDeadTranslations(?Closure $progress = null, ?array $ignore = null)
 * @method static array<string, array{ count: int, files: string[] }> getFilesByUsedTranslations(?SearchCodeServiceInterface $service, ?Closure $progress = null)
 * @method static array<string, string[]> getUsedTranslationsByFiles(?SearchCodeServiceInterface $service = null, ?Closure $progress = null)
 * @method static PhpTranslations|JsonTranslations setTranslations(string $locale, ?string $namespace, array $values)
 * @method static PhpTranslations|JsonTranslations setTranslation(string $locale, ?string $namespace, string $key, mixed $value)
 * @method static PhpTranslations|JsonTranslations translateTranslations(string $source, string $target, ?string $namespace, array $keys, ?TranslateServiceInterface $service = null)
 * @method static PhpTranslations|JsonTranslations translateTranslation(string $source, string $target, ?string $namespace, string $key, ?TranslateServiceInterface $service = null)
 * @method static PhpTranslations|JsonTranslations proofreadTranslations(string $locale, ?string $namespace, array $keys, ?ProofreadServiceInterface $service = null)
 * @method static PhpTranslations|JsonTranslations proofreadTranslation(string $locale, ?string $namespace, string $key, ?ProofreadServiceInterface $service = null)
 * @method static PhpTranslations|JsonTranslations deleteTranslations(string $locale, ?string $namespace, array $keys)
 * @method static PhpTranslations|JsonTranslations deleteTranslation(string $locale, ?string $namespace, string $key)
 * @method static PhpTranslations|JsonTranslations sortTranslations(string $locale, ?string $namespace)
 * @method static Collection<string, Collection<string, PhpTranslations|JsonTranslations>> sortAllTranslations()
 * @method static PhpTranslations|JsonTranslations transformTranslations(string $locale, ?string $namespace, Closure(PhpTranslations|JsonTranslations $translations):PhpTranslations|JsonTranslations $callback)
 * @method static bool saveTranslations(string $locale, ?string $namespace, PhpTranslations|JsonTranslations $translations)
 * @method static string getTranslationsPath(string $locale, ?string $namespace)
 * @method static PhpTranslations|JsonTranslations getNewTranslationsCollection(?string $namespace)
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
