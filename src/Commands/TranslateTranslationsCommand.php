<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Elegantly\Translator\TranslatorServiceProvider;
use Illuminate\Console\Command;

class TranslateTranslationsCommand extends Command
{
    public $signature = 'translator:translate {--from=} {--to=} {--service=} {--all} ';

    public $description = 'Translate translations from the given locale to the target one.';

    public function handle(): int
    {
        $from = (string) $this->option('from');
        $to = $this->option('to');
        $all = (bool) $this->option('all');
        $serviceName = $this->option('service');

        $service = TranslatorServiceProvider::getTranslateServiceFromConfig($serviceName);

        $namespaces = Translator::getNamespaces($from);

        $targets = collect($to ? [(string) $to] : Translator::getLanguages())->filter(fn ($locale) => $locale !== $from);

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

                $translations = Translator::translateTranslations(
                    $from,
                    $target,
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
