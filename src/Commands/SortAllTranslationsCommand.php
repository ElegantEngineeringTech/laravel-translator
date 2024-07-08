<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;

class SortAllTranslationsCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:sort 
                            {locales* : The locales to sort}
                            {--namespaces=* : The namespaces to sort}';

    public $description = 'Sort all translations using natural order';

    public function handle(): int
    {

        $locales = $this->argument('locales');
        $namespaces = $this->option('namespaces');

        progress(
            label: 'Sorting',
            steps: $locales,
            callback: function (string $locale, $progress) use ($namespaces) {
                $progress
                    ->label("Sorting {$locale}");

                foreach ($namespaces as $namespace) {
                    Translator::sortTranslations($locale, $namespace);
                }
            }
        );

        return self::SUCCESS;
    }

    public function promptForMissingArgumentsUsing()
    {
        return [
            'locales' => function () {
                $options = Translator::getLocales();

                return multiselect(
                    label: 'What locales would you like to sort?',
                    options: $options,
                    default: $options,
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

            $options = collect($input->getArgument('locales'))
                ->flatMap(fn (string $locale) => Translator::getNamespaces($locale))
                ->unique()
                ->toArray();


            $input->setOption('namespaces', multiselect(
                label: 'What namespaces would you like to sort?',
                options: $options,
                default: $options,
                required: true,
            ));
        }
    }
}
