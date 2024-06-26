<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

use function Laravel\Prompts\spin;

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
            label: 'What locales would you like to fix?'
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

                $translations = spin(
                    fn () => Translator::fixGrammarTranslations(
                        $locale,
                        $namespace,
                        $keys,
                        $service
                    ),
                    'Fetching response...'
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
