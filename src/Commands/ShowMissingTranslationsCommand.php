<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Helper\TableSeparator;

use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

class ShowMissingTranslationsCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:missing {locale : The locale of reference}';

    public $description = 'Show all missing translations present in the locale of reference but not in the others locales.';

    public function handle(): int
    {
        $reference = $this->argument('locale');

        $rows = [];

        $missing = Translator::getAllMissingTranslations($reference);

        foreach ($missing as $locale => $namespaces) {

            foreach ($namespaces as $namespace => $translations) {

                foreach ($translations as $translation) {
                    $rows[] = [$locale, "{$namespace}.{$translation}"];
                }

                if ($namespaces->keys()->last() !== $namespace) {
                    $rows[] = [new TableSeparator, new TableSeparator];
                }

            }

            if ($missing->keys()->last() !== $locale) {
                $rows[] = [new TableSeparator, new TableSeparator];
            }

        }

        table(
            headers: ['Locale', 'Missing key'],
            rows: $rows
        );

        return self::SUCCESS;
    }

    public function promptForMissingArgumentsUsing()
    {
        return [
            'locale' => function () {
                return select(
                    label: 'What is the locale of reference?',
                    options: Translator::getLocales(),
                    default: config('app.locale'),
                    required: true,
                );
            },

        ];
    }
}
