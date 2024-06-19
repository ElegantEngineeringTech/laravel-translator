<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Elegantly\Translator\TranslatorServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class FixGrammarTranslationsCommand extends Command
{
    public $signature = 'translator:grammar {--locales=} {--service=} ';

    public $description = 'Translate translations from the given locale to the target one.';

    public function handle(): int
    {
        $serviceArg = $this->option('service');
        $localesArg = explode(",", (string) $this->option('locales'));

        $service = TranslatorServiceProvider::getGrammarServiceFromConfig($serviceArg);

        $locales = collect(Translator::getLanguages())
            ->when($localesArg, fn (Collection $items) => $items->intersect($localesArg));


        foreach ($locales as $locale) {

            $this->info("Fixing grammar in '{$locale}' locale:");
            $this->line('Using service ' . get_class($service));

            $namespaces = Translator::getNamespaces($locale);

            foreach ($namespaces as $namespace) {

                $keys = Translator::getTranslations($locale, $namespace)
                    ->dot()
                    ->keys()
                    ->toArray();

                $this->line(count($keys) . " keys to fix found in {$locale}/{$namespace}.php");
                if (!$this->confirm("Would you like to continue?", true)) {
                    continue;
                }

                $translations = Translator::fixGrammarTranslations(
                    $locale,
                    $namespace,
                    $keys,
                    $service
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
