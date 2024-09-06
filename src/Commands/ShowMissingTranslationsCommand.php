<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Helper\TableSeparator;

use function Laravel\Prompts\select;

class ShowMissingTranslationsCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:missing {locale : The locale of reference}';

    public $description = 'Show all missing translations present in the locale of reference but not in the others locales.';

    public function handle(): int
    {
        $reference = $this->argument('locale');

        $rows = collect(Translator::getAllMissingTranslations($reference))
            ->flatMap(
                fn ($namespaces, string $locale) => $namespaces
                    ->flatMap(function (array $keys, string $namespace) use ($locale, $namespaces) {
                        $values = array_map(fn (string $key) => [$locale, "{$namespace}.$key"], $keys);

                        if ($namespaces->keys()->last() !== $namespace) {
                            $values[] = [new TableSeparator, new TableSeparator];
                        }

                        return $values;
                    })
            )->toArray();

        $this->table(
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
