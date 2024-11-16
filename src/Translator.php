<?php

namespace Elegantly\Translator;

use Closure;
use Elegantly\Translator\Collections\Translations;
use Elegantly\Translator\Drivers\Driver;
use Elegantly\Translator\Exceptions\TranslatorServiceException;
use Elegantly\Translator\Services\Proofread\ProofreadServiceInterface;
use Elegantly\Translator\Services\SearchCode\SearchCodeServiceInterface;
use Elegantly\Translator\Services\Translate\TranslateServiceInterface;
use Illuminate\Support\Arr;

class Translator
{
    final public function __construct(
        public Driver $driver,
        public ?TranslateServiceInterface $translateService = null,
        public ?ProofreadServiceInterface $proofreadService = null,
        public ?SearchCodeServiceInterface $searchcodeService = null,
    ) {
        //
    }

    public function driver(?string $name): static
    {
        return new static(
            driver: TranslatorServiceProvider::getDriverFromConfig($name),
            translateService: $this->translateService,
            proofreadService: $this->proofreadService,
            searchcodeService: $this->searchcodeService
        );
    }

    /**
     * @return array<int, string>
     */
    public function getLocales(): array
    {
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
     * @return array<string, array{ count: int, files: string[] }> The translations keys defined in the codebase but not defined in the driver
     */
    public function getUndefinedTranslations(string $locale): array
    {
        if (! $this->searchcodeService) {
            throw TranslatorServiceException::missingSearchcodeService();
        }

        $translations = $this->getTranslations($locale);

        $keys = $this->searchcodeService->filesByTranslations();

        return collect($keys)
            ->filter(function ($value, $key) use ($translations) {
                return ! $translations->has($key);
            })
            ->toArray();
    }

    /**
     * @return array<int, scalar|null> The translations keys defined in the driver but not defined in the codebase
     */
    public function getDeadTranslations(string $locale): array
    {
        if (! $this->searchcodeService) {
            throw TranslatorServiceException::missingSearchcodeService();
        }

        $translations = $this->getTranslations($locale);

        $keys = $this->searchcodeService->filesByTranslations();

        return $translations
            ->filter(function ($value, $key) use ($keys) {
                return ! Arr::has($keys, $key);
            })
            ->keys()
            ->toArray();
    }

    /**
     * @return array<int, scalar|null> The keys defined in source locale but not found in target locale
     */
    public function getMissingTranslations(
        string $source,
        string $target,
    ): array {

        $sourceTranslations = $this->getTranslations($source)->notBlank();
        $targetTranslations = $this->getTranslations($target)->notBlank();

        return $sourceTranslations
            ->filter(function ($value, $key) use ($targetTranslations) {
                return ! $targetTranslations->has($key);
            })
            ->toArray();
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
            sort: config()->boolean('translator.sort_keys'),
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
            sort: config()->boolean('translator.sort_keys'),
        );
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
            sort: config()->boolean('translator.sort_keys'),
        );
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
     *  @template T of Translations
     *
     * @param  Closure(T $translations):T  $callback
     * @return T
     */
    public function transformTranslations(
        string $locale,
        Closure $callback,
        bool $sort = false,
    ): Translations {

        $translations = $this->getTranslations($locale);

        $translations = $callback($translations);

        if ($sort) {
            $translations=$translations->sortKeys(SORT_NATURAL);
        }

        return $this->saveTranslations(
            $locale,
            $translations
        );

    }

    /**
     * @template T of Translations
     *
     * @param  T  $translations
     * @return T
     */
    public function saveTranslations(
        string $locale,
        Translations $translations,
    ): Translations {

        return $this->driver->saveTranslations(
            $locale,
            $translations
        );

    }

    public function clearCache(): void
    {
        $this->searchcodeService?->getCache()?->flush();
    }
}
