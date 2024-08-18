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
                            {source : The locale to translate} 
                            {target : The locale to translate to}
                            {--namespaces=* : The namespaces to translate}
                            {--service= : The translation service to use}
                            {--all : Translate not only missing keys but all keys}';

    public $description = 'Translate translations from the given locale to the target one.';

    public function handle(): int
    {
        $source = $this->argument('source');

        $target = $this->argument('target');

        $namespaces = $this->option('namespaces');

        $service = $this->option('service');

        $all = (bool) $this->option('all');

        progress(
            label: 'Translating',
            steps: $namespaces,
            callback: function (string $namespace, $progress) use ($source, $service, $target, $all) {
                $progress
                    ->label("Translating {$namespace}")
                    ->hint('Fetching response...');

                if ($all) {
                    $keys = Translator::getTranslations($source, $namespace)->toTranslationsKeys()->toArray();
                } else {
                    $keys = Translator::getMissingTranslations($source, $target, $namespace)->toArray();
                }

                return Translator::translateTranslations(
                    source: $source,
                    target: $target,
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
            'source' => function () {
                return select(
                    label: 'From what locale would you like to translate?',
                    options: Translator::getLocales(),
                    default: config('app.locale'),
                    required: true,
                );
            },
            'target' => function () {
                $options = collect(Translator::getLocales())
                    ->diff([$this->argument('source')])
                    ->values()
                    ->toArray();

                return select(
                    label: 'To what locale would you like to translate?',
                    options: $options,
                    required: true,
                );
            },
        ];
    }

    public function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        if (empty($input->getOption('namespaces'))) {
            $options = Translator::getNamespaces($input->getArgument('source'));

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
            $input->setOption('all', ! confirm(
                label: 'Only translate missing keys?',
                no: 'No, translate all keys'
            ));
        }
    }
}
