<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class MissingCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:missing {locale} {--sync : Add the missing keys to your driver} {--driver=}';

    public $description = 'Display all the translation keys found in the codebase but not in the driver.';

    public function handle(): int
    {
        $locale = $this->argument('locale');
        $sync = (bool) $this->option('sync');

        $translator = $this->getTranslator();

        $missing = $translator->getMissingTranslations($locale);

        intro('Using driver: '.$translator->driver::class);

        note(count($missing).' missing keys detected.');

        table(
            headers: ['Key', 'Count', 'Files'],
            rows: collect($missing)
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

        if ($sync) {

            $translator->setTranslations(
                locale: $locale,
                values: array_map(fn () => null, $missing)
            );

            info(count($missing).' missing keys added to the driver.');

        }

        return self::SUCCESS;
    }
}
