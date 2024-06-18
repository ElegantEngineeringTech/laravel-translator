<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

class SortAllTranslationsCommand extends Command
{
    public $signature = 'translator:sort';

    public $description = 'Sort all translations using natural order';

    public function handle(): int
    {
        $locales = Translator::getLanguages();

        foreach ($locales as $locale) {
            $this->newLine();
            $this->info("Sorting {$locale}");

            $this->withProgressBar(
                Translator::getNamespaces($locale),
                fn (string $namespace) => Translator::sortTranslations($locale, $namespace)
            );
        }

        return self::SUCCESS;
    }
}
