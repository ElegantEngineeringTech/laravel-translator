<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\TableSeparator;

class ShowDeadTranslationsCommand extends Command
{
    public $signature = 'translator:dead {--clear-cache}';

    public $description = 'Show all dead translations defined in translations files but not used in the codebase.';

    public function handle(): int
    {
        if ($this->option('clear-cache')) {
            Translator::clearCache();
        }

        $results = [
            ['.blade.php', 0, 0],
            ['.php', 0, 0],
        ];

        $bar = $this->output->createProgressBar();

        $translations = Translator::getAllDeadTranslations(
            progress: function (string $file, array $translations) use (&$results, $bar) {

                if (str($file)->endsWith('.blade.php')) {
                    // $this->line($file);

                    $results[0][2] += 1;
                    if (count($translations)) {
                        $results[0][1] += 1;
                    }
                } elseif (str($file)->endsWith('.php')) {
                    $results[1][2] += 1;
                    if (count($translations)) {
                        $results[1][1] += 1;
                    }
                }

                $bar->advance();

                // $this->output->write("<fg=green>✔️</>");
                // $this->output->write(".");
            }
        );

        $bar->finish();

        $this->newLine();

        $this->table(
            headers: ['Type', 'With translations', 'Total'],
            rows: $results,
        );

        $rows = collect($translations)
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
