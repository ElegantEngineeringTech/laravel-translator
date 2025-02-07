<?php

namespace Elegantly\Translator;

use Elegantly\Translator\Commands\AddLocaleCommand;
use Elegantly\Translator\Commands\ClearCacheCommand;
use Elegantly\Translator\Commands\DeadCommand;
use Elegantly\Translator\Commands\LocalesCommand;
use Elegantly\Translator\Commands\MissingCommand;
use Elegantly\Translator\Commands\ProofreadCommand;
use Elegantly\Translator\Commands\SortCommand;
use Elegantly\Translator\Commands\UntranslatedCommand;
use Elegantly\Translator\Contracts\ValidateLocales;
use Elegantly\Translator\Drivers\Driver;
use Elegantly\Translator\Drivers\JsonDriver;
use Elegantly\Translator\Drivers\PhpDriver;
use Elegantly\Translator\Services\Proofread\OpenAiService as ProofreadOpenAiService;
use Elegantly\Translator\Services\Proofread\ProofreadServiceInterface;
use Elegantly\Translator\Services\SearchCode\PhpParserService;
use Elegantly\Translator\Services\SearchCode\SearchCodeServiceInterface;
use Elegantly\Translator\Services\Translate\DeepLService;
use Elegantly\Translator\Services\Translate\OpenAiService;
use Elegantly\Translator\Services\Translate\TranslateServiceInterface;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TranslatorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-translator')
            ->hasConfigFile()
            ->hasCommands([
                SortCommand::class,
                LocalesCommand::class,
                AddLocaleCommand::class,
                DeadCommand::class,
                MissingCommand::class,
                UntranslatedCommand::class,
                ProofreadCommand::class,
                ClearCacheCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->scoped(Translator::class, function () {
            return new Translator(
                driver: static::getDriverFromConfig(),
                translateService: static::getTranslateServiceFromConfig(),
                proofreadService: static::getProofreadServiceFromConfig(),
                searchcodeService: static::getSearchcodeServiceFromConfig(),
            );
        });
    }

    public static function getDriverFromConfig(?string $driverName = null): Driver
    {
        $driver = $driverName ?? config()->string('translator.driver');

        return match ($driver) {
            'php' => PhpDriver::make(),
            'json' => JsonDriver::make(),
            default => $driver::make(),
        };
    }

    public static function getTranslateServiceFromConfig(?string $serviceName = null): ?TranslateServiceInterface
    {
        /** @var string|null $service */
        $service = $serviceName ?? config('translator.translate.service');

        if (! $service) {
            return null;
        }

        return match ($service) {
            'deepl' => DeepLService::make(),
            'openai' => OpenAiService::make(),
            default => $service::make(),
        };
    }

    public static function getProofreadServiceFromConfig(?string $serviceName = null): ?ProofreadServiceInterface
    {
        /** @var string|null $service */
        $service = $serviceName ?? config('translator.proofread.service');

        if (! $service) {
            return null;
        }

        return match ($service) {
            'openai' => ProofreadOpenAiService::make(),
            default => $service::make(),
        };
    }

    public static function getSearchcodeServiceFromConfig(?string $serviceName = null): ?SearchCodeServiceInterface
    {
        /** @var string|null $service */
        $service = $serviceName ?? config('translator.searchcode.service');

        if (! $service) {
            return null;
        }

        return match ($service) {
            'php-parser' => PhpParserService::make(),
            default => $service::make(),
        };
    }

    /**
     * @return ?array<int, string>
     */
    public static function getLocalesFromConfig(): ?array
    {
        /** @var array<int, string>|class-string<ValidateLocales> */
        $locales = config('translator.locales');

        if (is_array($locales)) {
            return $locales;
        }

        return null;
    }

    /**
     * @return null|class-string<ValidateLocales>
     */
    public static function getLocaleValidator(): ?string
    {
        /** @var array<int, string>|class-string<ValidateLocales> */
        $validator = config('translator.locales');

        if (is_array($validator)) {
            return null;
        }

        return $validator;
    }
}
