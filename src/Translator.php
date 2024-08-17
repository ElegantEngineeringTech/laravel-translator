<?php

namespace Elegantly\Translator;

use Closure;
use Elegantly\Translator\Collections\JsonTranslations;
use Elegantly\Translator\Collections\PhpTranslations;
use Elegantly\Translator\Exceptions\TranslatorException;
use Elegantly\Translator\Exceptions\TranslatorServiceException;
use Elegantly\Translator\Services\Proofread\ProofreadServiceInterface;
use Elegantly\Translator\Services\SearchCode\SearchCodeServiceInterface;
use Elegantly\Translator\Services\Translate\TranslateServiceInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class Translator
{
    const JSON_NAMESPACE = '_JSON_';

    public function __construct(
        public Filesystem $storage,
        public ?TranslateServiceInterface $translateService = null,
        public ?ProofreadServiceInterface $proofreadService = null,
        public ?SearchCodeServiceInterface $searchcodeService = null,
    ) {
        //
    }

    public function getTranslateService(): ?TranslateServiceInterface
    {
        return $this->translateService;
    }

    public function getProofreadService(): ?ProofreadServiceInterface
    {
        return $this->proofreadService;
    }

    public function getSearchcodeService(): ?SearchCodeServiceInterface
    {
        return $this->searchcodeService;
    }

    public function getJsonNamespace(): string
    {
        return static::JSON_NAMESPACE;
    }

    /**
     * @return string[]
     */
    public function getLocales(): array
    {
        return collect($this->storage->allDirectories())
            ->sort(SORT_NATURAL)
            ->values()
            ->toArray();
    }

    public function getNamespaces(string $locale): array
    {
        return collect($this->storage->allFiles($locale))
            ->filter(fn (string $file) => File::extension($file) === 'php')
            ->map(fn (string $file) => File::name($file))
            ->when(
                $this->storage->exists("{$locale}.json"),
                fn (Collection $collection) => $collection->push(static::JSON_NAMESPACE)
            )
            ->sort(SORT_NATURAL)
            ->values()
            ->toArray();
    }

    public function getTranslations(
        string $locale,
        ?string $namespace
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        /**
         * This function uses eval and not include
         * Because using 'include' would cache/compile the code in opcache
         * Therefore it would not reflect the changes after the file is edited
         */
        if ($content = $this->getTranslationsFileContent($locale, $namespace)) {

            return match ($namespace) {
                static::JSON_NAMESPACE => new JsonTranslations(json_decode($content, true)),
                default => new PhpTranslations(eval('?>'.$content)),
            };
        }

        return $this->getNewTranslationsCollection($namespace);
    }

    protected function getTranslationsFileContent(
        string $locale,
        ?string $namespace
    ): ?string {
        $namespace ??= static::JSON_NAMESPACE;

        $path = $this->getTranslationsPath($locale, $namespace);

        if ($this->storage->exists($path)) {
            return $this->storage->get($path);
        }

        return null;
    }

    /**
     * @return Collection<int, string>
     */
    public function getMissingTranslations(
        string $source,
        string $target,
        ?string $namespace,
    ): Collection {
        $namespace ??= static::JSON_NAMESPACE;

        return $this
            ->getTranslations($source, $namespace)
            ->diffTranslationsKeys(
                $this->getTranslations($target, $namespace)
            );
    }

    /**
     * Return all the translations keys present in the reference locale but not in the other ones
     *
     * @return Collection<string, Collection<string, Collection<int, string>>>
     */
    public function getAllMissingTranslations(
        string $source
    ): Collection {
        $locales = collect($this->getLocales())->diff([$source]);

        return $locales
            ->mapWithKeys(function (string $locale) use ($source) {
                $namespaces = collect($this->getNamespaces($locale))
                    ->mapWithKeys(fn (string $namespace) => [
                        $namespace => $this->getMissingTranslations($source, $locale, $namespace),
                    ])
                    ->filter();

                return [$locale => $namespaces];
            })
            ->filter();
    }

    /**
     * Retreives the translations keys from locale not used in any file
     *
     * @param  null|(Closure(string $file, string[] $translations):void)  $progress
     * @return Collection<int, string>
     */
    public function getDeadTranslations(
        string $locale,
        ?string $namespace,
        ?SearchCodeServiceInterface $service = null,
        ?Closure $progress = null,
        ?array $ignore = null,
    ): Collection {
        $namespace ??= static::JSON_NAMESPACE;
        $ignoredTranslations = $ignore ?? config('translator.searchcode.ignored_translations', []);

        $translationsKeys = $this
            ->getTranslations($locale, $namespace)
            ->toTranslationsKeys()
            ->reject(function (string $key) use ($namespace, $ignoredTranslations) {
                return match ($namespace) {
                    static::JSON_NAMESPACE => str($key)->startsWith($ignoredTranslations),
                    default => str("{$namespace}.{$key}")->startsWith($ignoredTranslations),
                };
            })
            ->values();

        $usedTranslationsKeys = array_keys($this->getFilesByUsedTranslations($service, $progress));

        return $translationsKeys
            ->reject(function (string $key) use ($usedTranslationsKeys, $namespace) {
                return match ($namespace) {
                    static::JSON_NAMESPACE => str($key)->startsWith($usedTranslationsKeys),
                    default => str("{$namespace}.{$key}")->startsWith($usedTranslationsKeys),
                };
            })
            ->values();
    }

    /**
     * @param  null|(Closure(string $file, string[] $translations):void)  $progress
     * @return Collection<string, Collection<string, Collection<int, string>>>
     */
    public function getAllDeadTranslations(
        ?Closure $progress = null,
        ?array $ignore = null,
    ): Collection {
        return collect($this->getLocales())
            ->mapWithKeys(function (string $locale) use ($progress, $ignore) {
                $namespaces = collect($this->getNamespaces($locale))
                    ->mapWithKeys(fn (string $namespace) => [
                        $namespace => $this->getDeadTranslations(
                            locale: $locale,
                            namespace: $namespace,
                            progress: $progress,
                            ignore: $ignore
                        ),
                    ])
                    ->filter();

                return [$locale => $namespaces];
            })
            ->filter();
    }

    /**
     * Retreives the translations keys used in the codebase
     *
     * @param  null|(Closure(string $file, string[] $translations):void)  $progress
     * @return array<string, array{ count: int, files: string[] }>
     */
    public function getFilesByUsedTranslations(
        ?SearchCodeServiceInterface $service = null,
        ?Closure $progress = null,
    ): array {
        $service ??= $this->searchcodeService;

        if (! $service) {
            throw TranslatorServiceException::missingSearchcodeService();
        }

        return $service->filesByTranslations($progress);
    }

    /**
     * Retreives the translations keys used in the codebase
     *
     * @param  null|(Closure(string $file, string[] $translations):void)  $progress
     * @return array<string, string[]>
     */
    public function getUsedTranslationsByFiles(
        ?SearchCodeServiceInterface $service = null,
        ?Closure $progress = null,
    ): array {
        $service ??= $this->searchcodeService;

        if (! $service) {
            throw TranslatorServiceException::missingSearchcodeService();
        }

        return $service->translationsByFiles($progress);
    }

    public function setTranslations(
        string $locale,
        ?string $namespace,
        array $values
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        if (empty($values)) {
            return $this->getNewTranslationsCollection($namespace);
        }

        return $this->transformTranslations(
            $locale,
            $namespace,
            function (PhpTranslations|JsonTranslations $translations) use ($values) {
                foreach ($values as $key => $value) {
                    $translations->set($key, $value);
                }

                if (config('translator.sort_keys')) {
                    $translations->sortNatural();
                }

                return $translations;
            }
        )->only(array_keys($values));
    }

    public function setTranslation(
        string $locale,
        ?string $namespace,
        string $key,
        mixed $value,
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        return $this->setTranslations($locale, $namespace, [
            $key => $value,
        ]);
    }

    public function translateTranslations(
        string $source,
        string $target,
        ?string $namespace,
        array $keys,
        ?TranslateServiceInterface $service = null,
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;
        $service = $service ?? $this->translateService;

        if (! $service) {
            throw TranslatorServiceException::missingTranslateService();
        }

        if (empty($keys)) {
            return $this->getNewTranslationsCollection($namespace);
        }

        return $this->transformTranslations(
            $target,
            $namespace,
            function (PhpTranslations|JsonTranslations $translations) use ($source, $target, $namespace, $keys, $service) {

                $sourceDotTranslations = $this->getTranslations($source, $namespace)
                    ->toDotTranslations()
                    ->only($keys)
                    ->filter(fn ($value) => ! blank($value))
                    ->toArray();

                $translatedValues = $service->translateAll(
                    $sourceDotTranslations,
                    $target
                );

                foreach ($translatedValues as $key => $value) {
                    $translations->set($key, $value);
                }

                if (config('translator.sort_keys')) {
                    $translations->sortNatural();
                }

                return $translations;
            }
        )->only($keys);
    }

    public function translateTranslation(
        string $source,
        string $target,
        ?string $namespace,
        string $key,
        ?TranslateServiceInterface $service = null,
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        return $this->translateTranslations(
            $source,
            $target,
            $namespace,
            [$key],
            $service
        );
    }

    public function proofreadTranslations(
        string $locale,
        ?string $namespace,
        array $keys,
        ?ProofreadServiceInterface $service = null,
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;
        $service = $service ?? $this->proofreadService;

        if (! $service) {
            throw TranslatorServiceException::missingProofreadService();
        }

        if (empty($keys)) {
            return $this->getNewTranslationsCollection($namespace);
        }

        return $this->transformTranslations(
            $locale,
            $namespace,
            function (PhpTranslations|JsonTranslations $translations) use ($service, $keys) {

                $fixedTranslations = $service->proofreadAll(
                    texts: $translations
                        ->toDotTranslations()
                        ->only($keys)
                        ->filter(fn ($value) => ! blank($value))
                        ->toArray()
                );

                foreach ($fixedTranslations as $key => $value) {
                    $translations->set($key, $value);
                }

                if (config('translator.sort_keys')) {
                    $translations->sortNatural();
                }

                return $translations;
            }
        );
    }

    public function proofreadTranslation(
        string $locale,
        ?string $namespace,
        string $key,
        ?ProofreadServiceInterface $service = null,
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        return $this->proofreadTranslations(
            $locale,
            $namespace,
            [$key],
            $service
        );
    }

    public function deleteTranslations(
        string $locale,
        ?string $namespace,
        array $keys,
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        return $this->transformTranslations(
            $locale,
            $namespace,
            function (PhpTranslations|JsonTranslations $translations) use ($keys) {
                $translations->forget($keys);

                return $translations;
            }
        );
    }

    public function deleteTranslation(
        string $locale,
        ?string $namespace,
        string $key,
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        return $this->deleteTranslations(
            $locale,
            $namespace,
            [$key]
        );
    }

    public function sortTranslations(
        string $locale,
        ?string $namespace
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        return $this->transformTranslations(
            $locale,
            $namespace,
            fn (PhpTranslations|JsonTranslations $translations) => $translations->sortNatural()
        );
    }

    /**
     * @return Collection<string, Collection<string, PhpTranslations|JsonTranslations>>
     */
    public function sortAllTranslations(): Collection
    {
        return collect($this->getLocales())
            ->mapWithKeys(function (string $locale) {
                $namespaces = collect($this->getNamespaces($locale))
                    ->mapWithKeys(function (string $namespace) use ($locale) {
                        return [$namespace => $this->sortTranslations($locale, $namespace)];
                    });

                return [$locale => $namespaces];
            });
    }

    /**
     * @param  Closure(PhpTranslations|JsonTranslations $translations):(PhpTranslations|JsonTranslations)  $callback
     */
    public function transformTranslations(
        string $locale,
        ?string $namespace,
        Closure $callback,
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        $translations = $this->getTranslations($locale, $namespace);
        $translations = $callback($translations);

        if ($this->saveTranslations($locale, $namespace, $translations)) {
            return $translations;
        }

        throw TranslatorException::write($locale, $namespace);
    }

    public function saveTranslations(
        string $locale,
        ?string $namespace,
        PhpTranslations|JsonTranslations $translations,
    ): bool {
        $namespace ??= static::JSON_NAMESPACE;

        return $this->storage->put(
            $this->getTranslationsPath($locale, $namespace),
            $translations->toFile()
        );
    }

    public function getTranslationsPath(
        string $locale,
        ?string $namespace
    ): string {
        $namespace ??= static::JSON_NAMESPACE;

        return match ($namespace) {
            static::JSON_NAMESPACE => "{$locale}.json",
            default => "{$locale}/{$namespace}.php",
        };
    }

    public function getNewTranslationsCollection(
        ?string $namespace
    ): PhpTranslations|JsonTranslations {
        $namespace ??= static::JSON_NAMESPACE;

        return match ($namespace) {
            static::JSON_NAMESPACE => new JsonTranslations,
            default => new PhpTranslations,
        };
    }

    public function clearCache(): void
    {
        $this->searchcodeService?->getCache()?->flush();
    }
}
