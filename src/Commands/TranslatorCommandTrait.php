<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Elegantly\Translator\Services\Grammar\GrammarServiceInterface;
use Elegantly\Translator\Services\Translate\TranslateServiceInterface;
use Elegantly\Translator\TranslatorServiceProvider;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

trait TranslatorCommandTrait
{
    public function getLocales(
        mixed $option,
        ?string $label = null
    ): array {
        $availableLocales = Translator::getLanguages();

        if ($option) {
            if (is_array($option)) {
                return array_intersect($availableLocales, $option);
            }
            if (is_string($option)) {
                return array_intersect($availableLocales, explode(',', $option));
            }

            return $availableLocales;
        }

        return multiselect(
            label: $label ?? 'In what locales?',
            options: $availableLocales,
            default: $availableLocales,
            required: true,
        );
    }

    public function getTranslateService(
        mixed $option,
        ?string $label = null,
    ): TranslateServiceInterface {

        if ($option && is_string($option)) {
            return TranslatorServiceProvider::getTranslateServiceFromConfig($option);
        }

        $serviceName = select(
            label: $label ?? 'What service would you like to use?',
            options: array_keys(config('translator.translate.services')),
            default: config('translator.translate.service'),
            required: true,
        );

        return TranslatorServiceProvider::getTranslateServiceFromConfig($serviceName);
    }

    public function getGrammarService(
        mixed $option,
        ?string $label = null,
    ): GrammarServiceInterface {

        if ($option && is_string($option)) {
            return TranslatorServiceProvider::getGrammarServiceFromConfig($option);
        }

        $serviceName = select(
            label: $label ?? 'What service would you like to use?',
            options: array_keys(config('translator.grammar.services')),
            default: config('translator.grammar.service'),
            required: true,
        );

        return TranslatorServiceProvider::getGrammarServiceFromConfig($serviceName);
    }
}
