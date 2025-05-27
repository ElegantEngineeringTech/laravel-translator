<?php

declare(strict_types=1);

namespace Elegantly\Translator;

use Closure;
use Elegantly\Translator\Collections\Translations;
use Elegantly\Translator\Drivers\Driver;
use Elegantly\Translator\Exceptions\TranslatorServiceException;
use Elegantly\Translator\Services\Exporter\ExporterInterface;
use Elegantly\Translator\Services\Proofread\ProofreadServiceInterface;
use Elegantly\Translator\Services\SearchCode\SearchCodeServiceInterface;
use Elegantly\Translator\Services\Translate\TranslateServiceInterface;

class Translator
{
    final public function __construct(
        public Driver $driver,
        public ?TranslateServiceInterface $translateService = null,
        public ?ProofreadServiceInterface $proofreadService = null,
        public ?SearchCodeServiceInterface $searchcodeService = null,
        public ?ExporterInterface $exporter = null,
    ) {
        //
    }

    public function driver(null|string|Driver $name): static
    {
        return new static(
            driver: $name instanceof Driver ? $name : TranslatorServiceProvider::getDriverFromConfig($name),
            translateService: $this->translateService,
            proofreadService: $this->proofreadService,
            searchcodeService: $this->searchcodeService,
            exporter: $this->exporter,
        );
    }

    public function withProofreadService(ProofreadServiceInterface $service): static
    {
        return new static(
            driver: $this->driver,
            translateService: $this->translateService,
            proofreadService: $service,
            searchcodeService: $this->searchcodeService,
            exporter: $this->exporter
        );
    }

    public function withTranslateService(TranslateServiceInterface $service): static
    {
        return new static(
            driver: $this->driver,
            translateService: $service,
            proofreadService: $this->proofreadService,
            searchcodeService: $this->searchcodeService,
            exporter: $this->exporter
        );
    }

    public function withSearchcodeService(SearchCodeServiceInterface $service): static
    {
        return new static(
            driver: $this->driver,
            translateService: $this->translateService,
            proofreadService: $this->proofreadService,
            searchcodeService: $service,
            exporter: $this->exporter
        );
    }

    /**
     * @return array<int, string>
     */
    public function getLocales(): array
    {
        if ($locales = TranslatorServiceProvider::getLocalesFromConfig()) {
            return $locales;
        }

        if ($validator = TranslatorServiceProvider::getLocaleValidator()) {
            return array_values(array_filter(
                $this->driver->getLocales(),
                fn ($locale) => $validator::make()->isValid($locale),
            ));
        }

        return $this->driver->getLocales();
    }

    public function getTranslations(string $locale): Translations
    {
        return $this->driver->getTranslations($locale);
    }

    public function collect(): Translations
    {
        return ($this->driver)::collect();
    }

    /**
     * Scan the codebase to find keys not present in the driver
     *
     * @param  null|(Closure(string $path):void)  $progress
     * @param  null|(Closure(int $total):void)  $start
     * @param  null|(Closure():void)  $end
     * @return array<string, array{ count: int, files: string[] }> The translations keys defined in the codebase but not defined in the driver
     */
    public function getMissingTranslations(
        string $locale,
        ?Closure $progress = null,
        ?Closure $start = null,
        ?Closure $end = null,
    ): array {
        if (! $this->searchcodeService) {
            throw TranslatorServiceException::missingSearchcodeService();
        }

        $translations = $this->getTranslations($locale);

        $keys = $this->searchcodeService->filesByTranslations(
            progress: $progress,
            start: $start,
            end: $end
        );

        return collect($keys)
            ->filter(function ($value, $key) use ($translations) {
                return ! $translations->has($key);
            })
            ->all();
    }

    /**
     * The translations defined in the driver but not defined in the codebase
     */
    public function getDeadTranslations(string $locale): Translations
    {
        if (! $this->searchcodeService) {
            throw TranslatorServiceException::missingSearchcodeService();
        }

        $defined = $this->searchcodeService->filesByTranslations();

        return $this
            ->getTranslations($locale)
            ->except(array_keys($defined));
    }

    public function getUntranslatedTranslations(
        string $source,
        string $target,
    ): Translations {

        $sourceTranslations = $this->getTranslations($source)->notBlank();
        $targetTranslations = $this->getTranslations($target)->notBlank();

        return $sourceTranslations->diff($targetTranslations);
    }

    /**
     * @param  array<string, scalar|null>  $values
     */
    public function setTranslations(
        string $locale,
        array $values
    ): Translations {

        if (empty($values)) {
            return $this->getTranslations($locale);
        }

        return $this->transformTranslations(
            locale: $locale,
            callback: function ($translations) use ($values) {
                return $translations->merge($values);
            },
            sort: (bool) config('translator.sort_keys'),
        );
    }

    public function setTranslation(
        string $locale,
        string $key,
        string|int|float|bool|null $value,
    ): Translations {
        return $this->setTranslations($locale, [$key => $value]);
    }

    /**
     * @param  array<int, string>  $keys
     */
    public function translateTranslations(
        string $source,
        string $target,
        array $keys,
        ?TranslateServiceInterface $service = null,
    ): Translations {
        $service = $service ?? $this->translateService;

        if (! $service) {
            throw TranslatorServiceException::missingTranslateService();
        }

        if (empty($keys)) {
            return $this->getTranslations($target);
        }

        return $this->transformTranslations(
            locale: $target,
            callback: function ($translations) use ($source, $target, $keys, $service) {

                $sourceTranslations = $this->getTranslations($source)
                    ->only($keys)
                    ->filter(fn ($value) => ! blank($value))
                    ->toArray();

                $translatedValues = $service->translateAll(
                    $sourceTranslations,
                    $target
                );

                return $translations->merge($translatedValues);
            },
            sort: (bool) config('translator.sort_keys'),
        )->only($keys);
    }

    public function translateTranslation(
        string $source,
        string $target,
        string $key,
        ?TranslateServiceInterface $service = null,
    ): Translations {
        return $this->translateTranslations(
            $source,
            $target,
            [$key],
            $service
        );
    }

    /**
     * @param  array<int, string>  $keys
     */
    public function proofreadTranslations(
        string $locale,
        array $keys,
        ?ProofreadServiceInterface $service = null,
    ): Translations {
        $service = $service ?? $this->proofreadService;

        if (! $service) {
            throw TranslatorServiceException::missingProofreadService();
        }

        if (empty($keys)) {
            return $this->getTranslations($locale);
        }

        return $this->transformTranslations(
            locale: $locale,
            callback: function ($translations) use ($service, $keys) {

                $proofreadValues = $service->proofreadAll(
                    texts: $translations
                        ->only($keys)
                        ->filter(fn ($value) => ! blank($value))
                        ->toArray()
                );

                return $translations->merge($proofreadValues);
            },
            sort: (bool) config('translator.sort_keys'),
        )->only($keys);
    }

    public function proofreadTranslation(
        string $locale,
        string $key,
        ?ProofreadServiceInterface $service = null,
    ): Translations {
        return $this->proofreadTranslations(
            $locale,
            [$key],
            $service
        );
    }

    /**
     * @param  array<int, string>  $keys
     */
    public function deleteTranslations(
        string $locale,
        array $keys,
    ): Translations {
        return $this->transformTranslations(
            $locale,
            function ($translations) use ($keys) {
                return $translations->except($keys);
            }
        );
    }

    public function deleteTranslation(
        string $locale,
        string $key,
    ): Translations {

        return $this->deleteTranslations(
            $locale,
            [$key]
        );
    }

    public function sortTranslations(
        string $locale,
    ): Translations {

        return $this->transformTranslations(
            locale: $locale,
            callback: fn ($translations) => $translations,
            sort: true,
        );
    }

    /**
     * @param  Closure(Translations $translations):Translations  $callback
     */
    public function transformTranslations(
        string $locale,
        Closure $callback,
        bool $sort = false,
    ): Translations {

        $translations = $this->getTranslations($locale);

        $translations = $callback($translations);

        if ($sort) {
            $translations = $translations->sortKeys(SORT_NATURAL);
        }

        return $this->saveTranslations(
            $locale,
            $translations
        );

    }

    public function saveTranslations(
        string $locale,
        Translations $translations,
    ): Translations {

        return $this->driver->saveTranslations(
            $locale,
            $translations
        );

    }

    public function exportTranslations(
        string $path,
        ?ExporterInterface $exporter = null
    ): string {
        $exporter = $exporter ?? $this->exporter;

        if (! $exporter) {
            throw TranslatorServiceException::missingExporterService();
        }

        $locales = $this->getLocales();

        $translationsByLocale = collect($locales)
            ->mapWithKeys(fn ($locale) => [$locale => $this->getTranslations($locale)])
            ->all();

        return $exporter->export($translationsByLocale, $path);

    }

    /**
     * @return array<string, array<int|string, scalar>>
     */
    public function importTranslations(
        string $path,
        ?ExporterInterface $exporter = null
    ): array {
        $exporter = $exporter ?? $this->exporter;

        if (! $exporter) {
            throw TranslatorServiceException::missingExporterService();
        }

        $translationsByLocale = $exporter->import($path);

        foreach ($translationsByLocale as $locale => $values) {

            $this->transformTranslations(
                locale: $locale,
                callback: function ($translations) use ($values) {
                    return $translations->merge($values);
                },
                sort: (bool) config('translator.sort_keys'),
            );

        }

        return $translationsByLocale;
    }

    public function clearCache(): void
    {
        $this->searchcodeService?->getCache()?->flush();
    }
}
