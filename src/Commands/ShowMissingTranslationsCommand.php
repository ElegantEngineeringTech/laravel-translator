<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

class ShowMissingTranslationsCommand extends Command
{
    public $signature = 'translator:missing {locale}';

    public $description = "Show all missing translations taking present in 'locale' but not in the others languages.";

    public function handle(): int
    {
        $reference = (string) $this->argument('locale');

        $this->table(
            headers: ['Language', 'Missing key'],
            rows: collect(Translator::getAllMissingTranslations($reference))
                ->flatMap(function (array $namespaces, string $locale) {
                    return collect($namespaces)
                        ->flatMap(fn (string $key, string $namespace) => [$locale, "{$namespace}.$key"]);
                })
        );

        return self::SUCCESS;
    }
}
