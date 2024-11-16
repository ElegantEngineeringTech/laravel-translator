<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

/**
 * Display translations strings found in codebase but not in a locale
 */
class MissingCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:missing {source} {target} {--translate} {--driver=}';

    public $description = 'Display all the translation keys not found in the codebase.';

    public function handle(): int
    {
        $source = (string) $this->argument('source');
        $target = (string) $this->argument('target');
        $translate = (bool) $this->option('translate');

        $translator = $this->getTranslator();

        $missing = $translator->getMissingTranslations($source, $target);
        $count = count($missing);

        intro('Using driver: '.$translator->driver::class);

        note("{$count} missing translations keys detected.");

        table(
            headers: ['Key', "Source {$source}"],
            rows: collect($missing)
                ->map(function ($value, $key) {
                    return [
                        $key,
                        str($value)->limit(50)->value(),
                    ];
                })->all()
        );

        if ($translate) {
            $translated = spin(function () use ($translator, $source, $target, $missing) {

                return $translator->translateTranslations(
                    source: $source,
                    target: $target,
                    keys: array_keys($missing)
                );

            }, "Translating the {$count} missing translations from '{$source}' to '{$target}'");

            table(
                headers: ['Key', "Source {$source}", "Target {$target}"],
                rows: collect($translated)
                    ->map(function ($value, $key) use ($missing) {
                        return [
                            $key,
                            str($missing[$key] ?? null)->limit(25)->value(),
                            str($value)->limit(25)->value(),
                        ];
                    })->all()
            );
        }

        return self::SUCCESS;
    }
}
