<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\TableSeparator;

class ShowMissingTranslationsCommand extends Command
{
    public $signature = 'translator:missing {locale}';

    public $description = "Show all missing translations taking present in 'locale' but not in the others languages.";

    public function handle(): int
    {
        $reference = (string) $this->argument('locale');

        $rows = collect(Translator::getAllMissingTranslations($reference))
            ->flatMap(
                fn (array $namespaces, string $locale) => collect($namespaces)
                    ->flatMap(function (array $keys, string $namespace) use ($locale, $namespaces) {
                        $values = array_map(fn (string $key) => [$locale, "{$namespace}.$key"], $keys);

                        if (array_key_last($namespaces) !== $namespace) {
                            $values[] = [new TableSeparator, new TableSeparator];
                        }

                        return $values;
                    })
            )->toArray();

        $this->table(
            headers: ['Language', 'Missing key'],
            rows: $rows
        );

        return self::SUCCESS;
    }
}
