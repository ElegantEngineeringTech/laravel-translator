<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

/**
 * Display translations strings found in codebase but not in a locale
 */
class UndefinedCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:undefined {locale} {--driver=}';

    public $description = 'Display all the translation keys not found in the specified locale.';

    public function handle(): int
    {
        $locale = $this->argument('locale');

        $translator = $this->getTranslator();

        $undefined = $translator->getUndefinedTranslations($locale);

        intro('Using driver: '.$translator->driver::class);

        note(count($undefined).' undefined translations keys detected.');

        table(
            headers: ['Key', 'Count', 'Files'],
            rows: collect($undefined)
                ->map(function ($value, $key) {
                    return [
                        str($key)->limit(20)->value(),
                        (string) $value['count'],
                        implode("\n",
                            array_map(
                                fn ($file) => str($file)->after(base_path()),
                                $value['files'],
                            )
                        ),
                    ];
                })->values()->all()
        );

        return self::SUCCESS;
    }
}
