<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class MissingCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:missing {locale} {--driver=}';

    public $description = 'Display all the translation keys found in the codebase but not in the driver.';

    public function handle(): int
    {
        $locale = $this->argument('locale');

        $translator = $this->getTranslator();

        $undefined = $translator->getMissingTranslations($locale);

        intro('Using driver: '.$translator->driver::class);

        note(count($undefined).' missing keys detected.');

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
