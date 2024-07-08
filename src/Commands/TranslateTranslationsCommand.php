<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Elegantly\Translator\TranslatorServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;

class TranslateTranslationsCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:translate 
                            {from : The locale to translate} 
                            {to : The locale to translate to}
                            {--namespaces=* : The namespaces to translate}
                            {--service= : The translation service to use}
                            {--all : Translate not only missing keys but all keys}';

    public $description = 'Translate translations from the given locale to the target one.';

    public function handle(): int
    {
        $from = $this->argument('from');

        $to = $this->argument('to');

        $namespaces = $this->option('namespaces');

        $service = $this->option('service');

        $all = (bool) $this->option('all');

        progress(
            label: 'Translating',
            steps: $namespaces,
            callback: function (string $namespace, $progress) use ($from, $service, $to, $all) {
                $progress
                    ->label("Translating {$namespace}")
                    ->hint('Fetching response...');

                if ($all) {
                    $keys = Translator::getTranslations($from, $namespace)->dot()->keys()->toArray();
                } else {
                    $keys = Translator::getMissingTranslations($from, $to, $namespace);
                }

                return Translator::translateTranslations(
                    referenceLocale: $from,
                    targetLocale: $to,
                    namespace: $namespace,
                    keys: $keys,
                    service: TranslatorServiceProvider::getTranslateServiceFromConfig($service)
                );
            },
            hint: 'This may take some time.',
        );

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

                return select(
                    label: 'In what locale would you like to translate?',
                    options: array_diff($options, [$this->argument('from')]),
                    required: true,
                );
            },
        ];
    }

    function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        if (empty($input->getOption('namespaces'))) {
            $options = Translator::getNamespaces($input->getArgument('from'));

            $input->setOption('namespaces', multiselect(
                label: 'What namespaces would you like to translate?',
                options: $options,
                default: $options,
                required: true,
            ));
        }

        if ($input->getOption('service') === null) {
            $input->setOption('service', select(
                label: 'What service would you like to use?',
                options: array_keys(config('translator.translate.services')),
                default: config('translator.translate.service'),
                required: true,
            ));
        }

        if ($input->getOption('all') === false) {
            $input->setOption('all', !confirm(
                label: 'Only translate missing keys?',
                no: 'No, translate all keys'
            ));
        }
    }
}
