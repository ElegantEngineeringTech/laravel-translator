<?php

namespace Elegantly\Translator;

use Elegantly\Translator\Commands\ShowMissingTranslationsCommand;
use Elegantly\Translator\Commands\SortAllTranslationsCommand;
use Elegantly\Translator\Commands\TranslateTranslationsCommand;
use Elegantly\Translator\Services\DeepLService;
use Elegantly\Translator\Services\OpenAiService;
use Elegantly\Translator\Services\TranslatorServiceInterface;
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
                ShowMissingTranslationsCommand::class,
                SortAllTranslationsCommand::class,
                TranslateTranslationsCommand::class,
            ]);
    }

    public function registeringPackage()
    {
        $this->app->scoped(Translator::class, function () {
            return new Translator(
                storage: Storage::build([
                    'driver' => 'local',
                    'root' => config('translator.lang_path'),
                ]),
                service: static::getTranslatorServiceFromConfig()
            );
        });
    }

    public static function getTranslatorServiceFromConfig(?string $serviceName = null): TranslatorServiceInterface
    {
        $service = config('translator.service');

        return match ($service) {
            DeepLService::class, 'deepl' => new DeepLService(
                key: config('translator.services.deepl.key')
            ),
            OpenAiService::class, 'openai' => new OpenAiService(
                model: config('translator.services.openai.model'),
                prompt: config('translator.services.openai.prompt'),
            ),
            null, '' => null,
            default => new $service(),
        };
    }
}
