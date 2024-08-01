<?php

namespace Elegantly\Translator;

use Elegantly\Translator\Commands\FixGrammarTranslationsCommand;
use Elegantly\Translator\Commands\ShowDeadTranslationsCommand;
use Elegantly\Translator\Commands\ShowMissingTranslationsCommand;
use Elegantly\Translator\Commands\SortAllTranslationsCommand;
use Elegantly\Translator\Commands\TranslateTranslationsCommand;
use Elegantly\Translator\Services\Grammar\GrammarServiceInterface;
use Elegantly\Translator\Services\Grammar\OpenAiService as GrammarOpenAiService;
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
                ShowMissingTranslationsCommand::class,
                SortAllTranslationsCommand::class,
                TranslateTranslationsCommand::class,
                FixGrammarTranslationsCommand::class,
                ShowDeadTranslationsCommand::class,
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
                translateService: static::getTranslateServiceFromConfig(),
                grammarService: static::getGrammarServiceFromConfig(),
                searchcodeService: static::getSearchcodeServiceFromConfig(),
            );
        });
    }

    public static function getTranslateServiceFromConfig(?string $serviceName = null): TranslateServiceInterface
    {
        $service = $serviceName ?? config('translator.translate.service');

        return match ($service) {
            'deepl', DeepLService::class => new DeepLService(
                key: config('translator.translate.services.deepl.key')
            ),
            'openai', OpenAiService::class => new OpenAiService(
                model: config('translator.translate.services.openai.model'),
                prompt: config('translator.translate.services.openai.prompt'),
            ),
            '', null => null,
            default => new $service,
        };
    }

    public static function getGrammarServiceFromConfig(?string $serviceName = null): GrammarServiceInterface
    {
        $service = $serviceName ?? config('translator.grammar.service');

        return match ($service) {
            'openai', GrammarOpenAiService::class => new GrammarOpenAiService(
                model: config('translator.grammar.services.openai.model'),
                prompt: config('translator.grammar.services.openai.prompt'),
            ),
            '', null => null,
            default => new $service,
        };
    }

    public static function getSearchcodeServiceFromConfig(?string $serviceName = null): SearchCodeServiceInterface
    {
        $service = $serviceName ?? config('translator.searchcode.service');

        return match ($service) {
            'php-parser', PhpParserService::class => new PhpParserService(
                config('translator.searchcode.services.regex.paths')
            ),
            '', null => null,
            default => new $service,
        };
    }
}
