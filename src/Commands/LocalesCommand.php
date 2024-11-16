<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;

class LocalesCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:locales {--driver=}';

    public $description = 'Retrieve the defined locales.';

    public function handle(): int
    {
        $translator = $this->getTranslator();

        $locales = $translator->getLocales();

        intro('Using driver: '.$translator->driver::class);

        note(count($locales).' locales defined.');

        info(implode(', ', $locales));

        return self::SUCCESS;
    }
}
