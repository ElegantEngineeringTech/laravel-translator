<?php

namespace Elegantly\Translator;

use Elegantly\Translator\Exceptions\TranslatorException;
use Elegantly\Translator\Exceptions\TranslatorServiceException;
use Elegantly\Translator\Services\Grammar\GrammarServiceInterface;
use Elegantly\Translator\Services\Translate\TranslateServiceInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

class Translator
{
    public function __construct(
        public Filesystem $storage,
        public ?TranslateServiceInterface $translateService = null,
        public ?GrammarServiceInterface $grammarService = null,
    ) {
        //
    }

    public function getLanguages(): array
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
            ->sort(SORT_NATURAL)
            ->values()
            ->toArray();
    }

    public function getTranslations(string $locale, string $namespace): Translations
    {
        $path = "{$locale}/{$namespace}.php";

        if ($this->storage->exists($path)) {
            return new Translations(
                items: include $this->storage->path($path),
            );
        }

        return new Translations();
    }

    /**
     * @return array<string, array<string, array>>
     */
    public function getAllMissingTranslations(
        string $referenceLocale
    ): array {
        $targets = collect($this->getLanguages())
            ->reject(fn ($locale) => $locale === $referenceLocale);

        return $targets->mapWithKeys(function (string $target) use ($referenceLocale) {
            $namespaces = $this->getNamespaces($target);

            return [
                $target => collect($namespaces)->mapWithKeys(fn (string $namespace) => [
                    $namespace => $this->getMissingTranslations($referenceLocale, $target, $namespace),
                ])->filter(),
            ];
        })->filter()->toArray();
    }

    public function getMissingTranslations(
        string $referenceLocale,
        string $targetLocale,
        string $namespace,
    ): array {
        $referenceTranslations = $this->getTranslations($referenceLocale, $namespace);
        $targetTranslations = $this->getTranslations($targetLocale, $namespace);

        return $referenceTranslations->getMissingTranslationsIn($targetTranslations);
    }

    /**
     * @param  array<string|int, string|int|float|array|null>  $values
     */
    public function setTranslations(
        string $locale,
        string $namespace,
        array $values
    ): Translations {

        if (count($values) === 0) {
            return new Translations();
        }

        return $this->transformTranslations(
            $locale,
            $namespace,
            function (Translations $translations) use ($values) {
                foreach ($values as $key => $value) {
                    $translations->set($key, $value);
                }

                return $translations;
            }
        )->only(array_keys($values));
    }

    public function setTranslation(
        string $locale,
        string $namespace,
        string $key,
        string|array|int|float|null $value,
    ): Translations {
        return $this->setTranslations($locale, $namespace, [
            $key => $value,
        ]);
    }

    public function translateTranslations(
        string $referenceLocale,
        string $targetLocale,
        string $namespace,
        array $keys,
        ?TranslateServiceInterface $service = null,
    ): Translations {
        $service = $service ?? $this->translateService;

        if (! $service) {
            throw TranslatorServiceException::missingTranslateService();
        }

        if (count($keys) === 0) {
            return new Translations();
        }

        return $this->transformTranslations(
            $targetLocale,
            $namespace,
            function (Translations $translations) use ($referenceLocale, $targetLocale, $namespace, $keys, $service) {

                $referenceTranslations = $this->getTranslations($referenceLocale, $namespace);

                $referenceValues = $referenceTranslations
                    ->toBase()
                    ->dot()
                    ->only($keys)
                    ->filter(fn ($value) => ! blank($value))
                    ->toArray();

                $translatedValues = $service->translateAll(
                    $referenceValues,
                    $targetLocale
                );

                foreach ($translatedValues as $key => $value) {
                    $translations->set($key, $value);
                }

                return $translations;
            }
        )->only($keys);
    }

    public function fixGrammarTranslations(
        string $locale,
        string $namespace,
        array $keys,
        ?GrammarServiceInterface $service = null,
    ): Translations {
        $service = $service ?? $this->grammarService;

        if (! $service) {
            throw TranslatorServiceException::missingGrammarService();
        }

        return $this->transformTranslations($locale, $namespace, function (Translations $translations) use ($service, $keys) {

            $fixedTranslations = $service->fixAll(
                texts: $translations
                    ->toBase()
                    ->dot()
                    ->only($keys)
                    ->filter(fn ($value) => ! blank($value))
                    ->toArray()
            );

            foreach ($fixedTranslations as $key => $value) {
                $translations->set($key, $value);
            }

            return $translations;
        });
    }

    public function translateTranslation(
        string $referenceLocale,
        string $targetLocale,
        string $namespace,
        string $key,
    ): Translations {
        return $this->translateTranslations(
            $referenceLocale,
            $targetLocale,
            $namespace,
            [$key]
        );
    }

    public function deleteTranslation(
        string $locale,
        string $namespace,
        string $key,
    ): Translations {
        return $this->deleteTranslations(
            $locale,
            $namespace,
            [$key]
        );
    }

    public function deleteTranslations(
        string $locale,
        string $namespace,
        array $keys,
    ): Translations {
        return $this->transformTranslations(
            $locale,
            $namespace,
            function (Translations $translations) use ($keys) {
                foreach ($keys as $key) {
                    $translations->forget($key);
                }

                return $translations;
            }
        );
    }

    public function sortTranslations(string $locale, string $namespace): Translations
    {
        return $this->transformTranslations(
            $locale,
            $namespace,
            fn (Translations $translations) => $translations->sortNatural()
        );
    }

    public function sortAllTranslations(): void
    {
        foreach ($this->getLanguages() as $locale) {
            foreach ($this->getNamespaces($locale) as $namespace) {
                $this->sortTranslations($locale, $namespace);
            }
        }
    }

    /**
     * @param  callable(Translations $translations):Translations  $callback
     */
    public function transformTranslations(
        string $locale,
        string $namespace,
        callable $callback,
    ): Translations {
        $translations = $this->getTranslations($locale, $namespace);
        $translations = $callback($translations);

        if ($this->saveTranslations($locale, $namespace, $translations)) {
            return $translations;
        }

        throw TranslatorException::write($locale, $namespace);
    }

    public function saveTranslations(
        string $locale,
        string $namespace,
        Translations $translations,
    ): bool {
        $content = "<?php\n\nreturn [";

        $content .= $translations->toFile();

        $content .= "\n];\n";

        return $this->storage->put(
            "{$locale}/{$namespace}.php",
            $content
        );
    }
}
