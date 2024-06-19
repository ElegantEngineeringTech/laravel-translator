<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SortAllTranslationsCommand extends Command
{
    public $signature = 'translator:sort {--locales=} {--namespaces=}';

    public $description = 'Sort all translations using natural order';

    public function handle(): int
    {
        $namespacesArg = $this->option('namespaces');
        $localesArg = $this->option('locales');

        $locales = collect(Translator::getLanguages())
            ->when($localesArg, fn (Collection $items) => $items->intersect(explode(',', $localesArg)));

        foreach ($locales as $locale) {
            $this->newLine();
            $this->info("Sorting {$locale}");

            $namespaces = collect(Translator::getNamespaces($locale))
                ->when($namespacesArg, fn (Collection $items) => $items->intersect(explode(',', $namespacesArg)));

            $this->withProgressBar(
                $namespaces,
                fn (string $namespace) => Translator::sortTranslations($locale, $namespace)
            );
        }

        return self::SUCCESS;
    }
}
