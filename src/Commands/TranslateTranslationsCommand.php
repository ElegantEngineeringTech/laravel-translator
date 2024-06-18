<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

class TranslateTranslationsCommand extends Command
{
    public $signature = 'translator:translate {--from=} {--to=} {--all}';

    public $description = 'Translate translations from the given locale to the target one.';

    public function handle(): int
    {
        $from = (string) $this->argument('from');
        $to = $this->argument('to');
        $all = (bool) $this->argument('all');

        $namespaces = Translator::getNamespaces($from);

        $targets = collect($to ? [(string) $to] : Translator::getLanguages())->filter(fn ($locale) => $locale !== $from);

        foreach ($targets as $target) {

            $this->info("Translating from {$from} to {$target}:");

            foreach ($namespaces as $namespace) {

                if ($all) {
                    $keys = Translator::getTranslations($from, $namespace)->dot()->keys()->toArray();
                } else {
                    $keys = Translator::getMissingTranslations($from, $target, $namespace);
                }

                if (! $this->confirm(count($keys)." found for {$target}.{$namespace}, would you like to continue?")) {
                    continue;
                }

                $translations = Translator::translateTranslations(
                    $from,
                    $target,
                    $namespace,
                    $keys
                );

                $this->table(
                    headers: ['key', 'Translation'],
                    rows: $translations->dot()->map(fn ($value, $key) => ["{$namespace}.{$key}", $value])
                );
            }
        }

        return self::SUCCESS;
    }
}
