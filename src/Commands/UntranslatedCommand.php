<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class UntranslatedCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:untranslated {source} {target} {--translate} {--driver=}';

    public $description = 'Display all the translation keys defined in the source locale but not in the target locale.';

    public function handle(): int
    {
        $source = (string) $this->argument('source');
        $target = (string) $this->argument('target');
        $translate = (bool) $this->option('translate');

        $translator = $this->getTranslator();

        $missing = $translator->getUntranslatedTranslations($source, $target);
        $count = count($missing);

        intro('Using driver: '.$translator->driver::class);

        note("{$count} untranslated keys detected.");

        table(
            headers: ['Key', "Source {$source}"],
            rows: collect($missing)
                ->map(fn ($value, $key) => [
                    (string) $key,
                    (string) str($value)->limit(50),
                ])->toArray()
        );

        if ($translate) {
            $translated = spin(function () use ($translator, $source, $target, $missing) {

                return $translator->translateTranslations(
                    source: $source,
                    target: $target,
                    keys: array_keys($missing)
                );

            }, "Translating the {$count} translations from '{$source}' to '{$target}'");

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
