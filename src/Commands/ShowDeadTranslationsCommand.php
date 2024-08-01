<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\TableSeparator;

class ShowDeadTranslationsCommand extends Command
{
    public $signature = 'translator:dead';

    public $description = 'Show all dead translations defined in translations files but not used in the codebase.';

    public function handle(): int
    {
        $rows = collect(Translator::getAllDeadTranslations())
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
            headers: ['Language', 'Dead key'],
            rows: $rows
        );

        return self::SUCCESS;
    }
}
