<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class TranslateTranslationsCommand extends Command
{
    use TranslatorCommandTrait;

    public $signature = 'translator:translate {--from=} {--to=} {--service=} {--all} ';

    public $description = 'Translate translations from the given locale to the target one.';

    public function getFrom(): string
    {
        $options = Translator::getLanguages();

        $from = $this->option('from');

        if (is_string($from) && in_array($from, $options)) {
            return $from;
        }

        return select(
            label: 'From what locale would you like to translate?',
            options: $options,
            default: config('app.locale'),
        );
    }

    public function handle(): int
    {
        $all = (bool) $this->option('all');
        $from = $this->getFrom();

        $service = $this->getTranslateService($this->option('service'));

        $targets = $this->getLocales(
            option: $this->option('to'),
            options: array_diff(Translator::getLanguages(), [$from]),
            label: 'In what locales would you like to translate?'
        );

        $namespacesOptions = Translator::getNamespaces($from);

        $namespaces = multiselect(
            label: 'What namespaces would you like to translate?',
            options: $namespacesOptions,
            default: $namespacesOptions,
        );

        info('Translating using service: '.get_class($service));

        foreach ($targets as $target) {
            info("Translating from {$from} to {$target}");

            progress(
                label: 'Translating',
                steps: $namespaces,
                callback: function (string $namespace, $progress) use ($from, $service, $target, $all) {
                    $progress
                        ->label("Translating {$namespace}")
                        ->hint('Fetching response...');

                    if ($all) {
                        $keys = Translator::getTranslations($from, $namespace)->dot()->keys()->toArray();
                    } else {
                        $keys = Translator::getMissingTranslations($from, $target, $namespace);
                    }

                    return Translator::translateTranslations(
                        $from,
                        $target,
                        $namespace,
                        $keys,
                        $service
                    );
                },
                hint: 'This may take some time.',
            );
        }

        // foreach ($targets as $target) {

        //     $this->info("Translating from '{$from}' to '{$target}':");

        //     foreach ($namespaces as $namespace) {

        //         if ($all) {
        //             $keys = Translator::getTranslations($from, $namespace)->dot()->keys()->toArray();
        //         } else {
        //             $keys = Translator::getMissingTranslations($from, $target, $namespace);
        //         }

        //         $this->line(count($keys) . " keys to translate found in {$target}/{$namespace}.php");

        //         if (!count($keys)) {
        //             continue;
        //         }

        //         if (!$this->confirm('Would you like to continue?', true)) {
        //             continue;
        //         }

        //         $translations = spin(
        //             fn () => Translator::translateTranslations(
        //                 $from,
        //                 $target,
        //                 $namespace,
        //                 $keys,
        //                 $service
        //             ),
        //             'Fetching response...'
        //         );

        //         $this->table(
        //             headers: ['key', 'Translation'],
        //             rows: $translations->dot()->map(fn ($value, $key) => ["{$namespace}.{$key}", $value])
        //         );
        //     }
        // }

        return self::SUCCESS;
    }
}
