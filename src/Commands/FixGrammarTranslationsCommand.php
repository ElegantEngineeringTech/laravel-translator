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

class FixGrammarTranslationsCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:grammar
                            {locale : The locale to fix}
                            {--namespaces=* : The namespaces to fix}
                            {--service= : The service to use}';

    public $description = 'Fix grammar in the given locale translations.';

    public function handle(): int
    {
        $locale = $this->argument('locale');

        $service = $this->option('service');

        $namespaces = $this->option('namespaces');

        progress(
            label: 'Fixing grammar',
            steps: $namespaces,
            callback: function (string $namespace, Progress $progress) use ($locale, $service) {
                $progress->label("Fixing {$namespace}");

                $keys = Translator::getTranslations($locale, $namespace)
                    ->dot()
                    ->keys()
                    ->toArray();

                Translator::fixGrammarTranslations(
                    locale: $locale,
                    namespace: $namespace,
                    keys: $keys,
                    service: TranslatorServiceProvider::getGrammarServiceFromConfig($service)
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
                    label: 'What locale would you like to fix?',
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
                label: 'What namespaces would you like to fix?',
                options: $options,
                default: $options,
                required: true,
            ));
        }

        if ($input->getOption('service') === null) {
            $input->setOption('service', select(
                label: 'What service would you like to use?',
                options: array_keys(config('translator.grammar.services')),
                default: config('translator.grammar.service'),
                required: true,
            ));
        }
    }
}
