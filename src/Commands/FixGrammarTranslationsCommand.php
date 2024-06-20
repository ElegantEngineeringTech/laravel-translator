<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

class FixGrammarTranslationsCommand extends Command
{
    use TranslatorCommandTrait;

    public $signature = 'translator:grammar {--locales=} {--service=} ';

    public $description = 'Translate translations from the given locale to the target one.';

    public function handle(): int
    {
        $service = $this->getGrammarService($this->option('service'));
        $locales = $this->getLocales(
            option: $this->option('locales'),
            label: 'In what locales would you like to translate?'
        );

        foreach ($locales as $locale) {

            $this->info("Fixing grammar in '/{$locale}':");
            $this->line('Using service :'.get_class($service));

            $namespaces = Translator::getNamespaces($locale);

            foreach ($namespaces as $namespace) {

                $keys = Translator::getTranslations($locale, $namespace)
                    ->dot()
                    ->keys()
                    ->toArray();

                $this->line(count($keys)." keys to fix found in {$locale}/{$namespace}.php");

                if (! count($keys)) {
                    continue;
                }

                if (! $this->confirm('Would you like to continue?', true)) {
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
