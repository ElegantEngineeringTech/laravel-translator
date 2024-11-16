<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;

class AddLocaleCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:add-locale {locale} {source?} {--driver=}';

    public $description = 'Add a new locale with all keys';

    public function handle(): int
    {
        $locale = (string) $this->argument('locale');
        $source = (string) $this->argument('source');

        $translator = $this->getTranslator();

        $locales = $translator->getLocales();

        intro('Using driver: '.$translator->driver::class);

        if (in_array($locale, $locales)) {
            info("{$locale} already exists.");

            return self::SUCCESS;
        }

        $translations = $source ? $translator->getTranslations($source) : $translator->collect();

        $translator->saveTranslations(
            $locale,
            $translations->map(fn () => null)
        );

        info("{$source} added with {$translations->count()} keys.");

        return self::SUCCESS;
    }
}
