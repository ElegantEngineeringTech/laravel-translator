<?php

namespace Elegantly\Translator;

use Elegantly\Translator\Commands\AddLocaleCommand;
use Elegantly\Translator\Commands\ClearCacheCommand;
use Elegantly\Translator\Commands\DeadCommand;
use Elegantly\Translator\Commands\LocalesCommand;
use Elegantly\Translator\Commands\MissingCommand;
use Elegantly\Translator\Commands\SortCommand;
use Elegantly\Translator\Commands\UntranslatedCommand;
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
use Illuminate\Support\Facades\Storage;
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
                ClearCacheCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->scoped(Translator::class, function () {
            return new Translator(
                driver: static::getDriverFromConfig(),
                translateService: static::getTranslateServiceFromConfig(),
                proofreadService: static::getproofreadServiceFromConfig(),
                searchcodeService: static::getSearchcodeServiceFromConfig(),
            );
        });
    }

    public static function getDriverFromConfig(?string $driverName = null): Driver
    {
        $driver = $driverName ?? config('translator.driver');

        return match ($driver) {
            'php', PhpDriver::class => PhpDriver::make(),
            'json', JsonDriver::class => JsonDriver::make(),
            '', null => null,
            default => new $driver,
        };
    }

    public static function getTranslateServiceFromConfig(?string $serviceName = null): ?TranslateServiceInterface
    {
        $service = $serviceName ?? config('translator.translate.service');

        return match ($service) {
            'deepl', DeepLService::class => new DeepLService(
                key: config('translator.services.deepl.key')
            ),
            'openai', OpenAiService::class => new OpenAiService(
                apiKey: config('translator.services.openai.key') ?? config('translator.translate.services.openai.key'),
                organization: config('translator.services.openai.organization') ?? config('translator.translate.services.openai.organization'),
                timeout: config('translator.services.openai.request_timeout') ?? config('translator.translate.services.openai.request_timeout') ?? 120,
                model: config('translator.translate.services.openai.model'),
                prompt: config('translator.translate.services.openai.prompt'),
            ),
            '', null => null,
            default => new $service,
        };
    }

    public static function getproofreadServiceFromConfig(?string $serviceName = null): ?ProofreadServiceInterface
    {
        $service = $serviceName ?? config('translator.proofread.service');

        return match ($service) {
            'openai', ProofreadOpenAiService::class => new ProofreadOpenAiService(
                apiKey: config('translator.services.openai.key') ?? config('translator.translate.services.openai.key'),
                organization: config('translator.services.openai.organization') ?? config('translator.translate.services.openai.organization'),
                timeout: config('translator.services.openai.request_timeout') ?? config('translator.translate.services.openai.request_timeout') ?? 120,
                model: config('translator.proofread.services.openai.model'),
                prompt: config('translator.proofread.services.openai.prompt'),
            ),
            '', null => null,
            default => new $service,
        };
    }

    public static function getSearchcodeServiceFromConfig(?string $serviceName = null): ?SearchCodeServiceInterface
    {
        $service = $serviceName ?? config('translator.searchcode.service');

        return match ($service) {
            'php-parser', PhpParserService::class => new PhpParserService(
                paths: config('translator.searchcode.paths'),
                excludedPaths: config('translator.searchcode.excluded_paths', []),
                cacheStorage: config('translator.searchcode.services.php-parser.cache_path')
                    ? Storage::build([
                        'driver' => 'local',
                        'root' => config('translator.searchcode.services.php-parser.cache_path'),
                    ])
                    : null,
            ),
            '', null => null,
            default => new $service,
        };
    }
}
