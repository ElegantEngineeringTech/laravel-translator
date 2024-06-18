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

        $service = $serviceName
            ? TranslatorServiceProvider::getTranslatorServiceFromConfig((string) $serviceName)
            : null;

        $namespaces = Translator::getNamespaces($from);

        $targets = collect($to ? [(string) $to] : Translator::getLanguages())->filter(fn ($locale) => $locale !== $from);

        foreach ($targets as $target) {

            $this->info("Translating from '{$from}' to '{$target}':");

            foreach ($namespaces as $namespace) {

                if ($all) {
                    $keys = Translator::getTranslations($from, $namespace)->dot()->keys()->toArray();
                } else {
                    $keys = Translator::getMissingTranslations($from, $target, $namespace);
                }

                if (! $this->confirm(count($keys)." keys to translate found in {$target}.{$namespace}.php, would you like to continue?", true)) {
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
