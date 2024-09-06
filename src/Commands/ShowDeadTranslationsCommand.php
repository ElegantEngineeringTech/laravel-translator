<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laravel\Prompts\Progress;
use Laravel\Prompts\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\progress;

class ShowDeadTranslationsCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:dead {locales* : The locales to scan} {--clear-cache}';

    public $description = 'Show all dead translations defined in translations files but not used in the codebase.';

    public function handleLocale(string $locale): array
    {
        $namespaces = Translator::getNamespaces($locale);

        return progress(
            "Scanning files: {$locale}",
            $namespaces,
            function (string $namespace, Progress $progress) use ($locale) {
                $progress->hint($namespace);

                $translations = Translator::getDeadTranslations(
                    locale: $locale,
                    namespace: $namespace,
                );

                return [$namespace, $translations];
            }
        );
    }

    public function displayLocale(string $locale)
    {
        $result = collect($this->handleLocale($locale));

        alert($result->flatten()->count().' dead translation keys found.');

        $table = new Table(['Translation Keys'], []);

        $length = $result->count();

        foreach ($result as $index => [$namespace, $items]) {
            $count = count($items);

            $table->rows[] = ["<info>{$namespace}</info> : <fg=gray>{$count}</>"];
            $table->rows[] = new TableSeparator;

            if (! $count) {
                continue;
            }

            foreach ($items as $item) {
                if ($namespace === Translator::getJsonNamespace()) {
                    $table->rows[] = [new TableCell($item)];
                } else {
                    $table->rows[] = [new TableCell("{$namespace}.{$item}")];
                }
            }

            if ($index < $length - 1) {
                $table->rows[] = new TableSeparator;
            }
        }

        $table->display();
    }

    public function handle(): int
    {
        if ($this->option('clear-cache')) {
            Translator::clearCache();
        }

        $locales = $this->argument('locales');

        foreach ($locales as $index => $locale) {

            $this->displayLocale($locale);

            if ($nextLocale = $locales[$index + 1] ?? null) {
                pause("Press enter to continue with '{$nextLocale}'...");
            }
        }

        return self::SUCCESS;
    }

    public function promptForMissingArgumentsUsing()
    {
        return [
            'locales' => function () {
                return multiselect(
                    label: 'What locales would you like to scan?',
                    options: Translator::getLocales(),
                    default: [config('app.locale')],
                    required: true,
                );
            },

        ];
    }
}
