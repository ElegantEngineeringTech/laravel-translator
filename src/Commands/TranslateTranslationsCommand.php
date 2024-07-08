<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Elegantly\Translator\TranslatorServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;

class TranslateTranslationsCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:translate 
                            {from : The locale to translate} 
                            {to : The locale to translate to}
                            {--service= : The translation service to use}
                            {--namespaces= : The namespaces to translate}
                            {--all : Translate not only missing keys but all keys}';

    public $description = 'Translate translations from the given locale to the target one.';

    public function handle(): int
    {
        $service = $this->option('service');

        $from = $this->argument('from');

        $to = $this->argument('to');

        $targets = is_string($to) ? explode(',', $to) : $to;

        $all = (bool) $this->option('all');

        $namespacesOption = $this->option('namespaces');

        $namespaces = is_string($namespacesOption) ? explode(',', $namespacesOption) : $namespacesOption;

        foreach ($targets as $target) {
            info("Translating from {$from} to {$target}");

            progress(
                label: 'Translating',
                steps: $namespaces,
                callback: function (string $namespace, $progress) use ($from, $service, $target, $all) {
                    $progress
                        ->label("Translating {$namespace}")
                        ->hint('Fetching response...');

                    if ($all) {
                        $keys = Translator::getTranslations($from, $namespace)->dot()->keys()->toArray();
                    } else {
                        $keys = Translator::getMissingTranslations($from, $target, $namespace);
                    }

                    return Translator::translateTranslations(
                        $from,
                        $target,
                        $namespace,
                        $keys,
                        TranslatorServiceProvider::getTranslateServiceFromConfig($service)
                    );
                },
                hint: 'This may take some time.',
            );
        }

        return self::SUCCESS;
    }

    public function promptForMissingArgumentsUsing()
    {
        return [
            'from' => function () {
                return select(
                    label: 'From what locale would you like to translate?',
                    options: Translator::getLocales(),
                    default: config('app.locale'),
                    required: true,
                );
            },
            'to' => function () {
                $options = Translator::getLocales();

                return multiselect(
                    label: 'In what locales would you like to translate?',
                    options: $options,
                    default: array_diff($options, [$this->argument('from')]),
                    required: true,
                );
            },
            'service' => function () {
                return select(
                    label: 'What service would you like to use?',
                    options: array_keys(config('translator.translate.services')),
                    default: config('translator.translate.service'),
                    required: true,
                );
            },
            'all' => function () {
                return confirm(
                    label: 'Only translate missing keys?',
                    no: 'No, translate all keys'
                );
            },
            'namespaces' => function () {
                $options = Translator::getNamespaces($this->argument('from'));

                return multiselect(
                    label: 'What namespaces would you like to translate?',
                    options: $options,
                    default: $options,
                    required: true,
                );
            },
        ];
    }
}
