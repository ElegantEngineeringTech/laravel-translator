<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Elegantly\Translator\TranslatorServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laravel\Prompts\Progress;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;

class ProofreadTranslationsCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:proofread
                            {locale : The locale to fix}
                            {--namespaces=* : The namespaces to fix}
                            {--service= : The service to use}';

    public $description = 'Proofread the translations in the given locale.';

    public function handle(): int
    {
        $locale = $this->argument('locale');

        $service = $this->option('service');

        $namespaces = $this->option('namespaces');

        progress(
            label: 'Proofreading',
            steps: $namespaces,
            callback: function (string $namespace, Progress $progress) use ($locale, $service) {
                $progress->label("Proofreading {$namespace}");

                $keys = Translator::getTranslations($locale, $namespace)
                    ->dot()
                    ->keys()
                    ->toArray();

                Translator::proofreadTranslations(
                    locale: $locale,
                    namespace: $namespace,
                    keys: $keys,
                    service: TranslatorServiceProvider::getproofreadServiceFromConfig($service)
                );
            },
        );

        return self::SUCCESS;
    }

    public function promptForMissingArgumentsUsing()
    {
        return [
            'locale' => function () {
                return select(
                    label: 'What locale would you like to proofread?',
                    options: Translator::getLocales(),
                    default: config('app.locale'),
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
            $options = Translator::getNamespaces($this->argument('locale'));

            $input->setOption('namespaces', multiselect(
                label: 'What namespaces would you like to proofread?',
                options: $options,
                default: $options,
                required: true,
            ));
        }

        if ($input->getOption('service') === null) {
            $input->setOption('service', select(
                label: 'What service would you like to use?',
                options: array_keys(config('translator.proofread.services')),
                default: config('translator.proofread.service'),
                required: true,
            ));
        }
    }
}
