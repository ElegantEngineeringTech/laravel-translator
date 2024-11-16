<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\table;

/**
 * Display translations strings found in codebase but not in a locale
 */
class SortCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:sort {locale} {--driver=}';

    public $description = 'Sort all the translation keys in the specified locale using natural order.';

    public function handle(): int
    {
        $locale = $this->argument('locale');

        $translator = $this->getTranslator();

        intro('Using driver: '.$translator->driver::class);

        $tranlations = $translator->sortTranslations($locale);

        table(
            headers: ['Key', 'Translation'],
            rows: $tranlations
                ->map(function ($value, $key) {
                    return [
                        $key,
                        str($value)->limit(50),
                    ];
                })->all()
        );

        return self::SUCCESS;
    }
}
