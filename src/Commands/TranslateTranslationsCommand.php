<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

use function Laravel\Prompts\spin;

class TranslateTranslationsCommand extends Command
{
    use TranslatorCommandTrait;

    public $signature = 'translator:translate {--from=} {--to=} {--service=} {--all} ';

    public $description = 'Translate translations from the given locale to the target one.';

    public function handle(): int
    {
        $all = (bool) $this->option('all');
        $from = (string) $this->option('from');

        $service = $this->getTranslateService($this->option('service'));
        $targets = $this->getLocales(
            option: $this->option('to'),
            label: 'In what locales would you like to translate?'
        );

        $namespaces = Translator::getNamespaces($from);

        foreach ($targets as $target) {

            $this->info("Translating from '{$from}' to '{$target}':");
            $this->line('Using service: '.get_class($service));

            foreach ($namespaces as $namespace) {

                if ($all) {
                    $keys = Translator::getTranslations($from, $namespace)->dot()->keys()->toArray();
                } else {
                    $keys = Translator::getMissingTranslations($from, $target, $namespace);
                }

                $this->line(count($keys)." keys to translate found in {$target}/{$namespace}.php");

                if (! count($keys)) {
                    continue;
                }

                if (! $this->confirm('Would you like to continue?', true)) {
                    continue;
                }

                $translations = spin(
                    fn () => Translator::translateTranslations(
                        $from,
                        $target,
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
