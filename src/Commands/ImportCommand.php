<?php

declare(strict_types=1);

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;

/**
 * Display translations strings found in codebase but not in a locale
 */
class ImportCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:import {path} {--driver=}';

    public $description = 'Import all the translations from a file.';

    public function handle(): int
    {
        /** @var string $path */
        $path = $this->argument('path');

        $translator = $this->getTranslator();

        intro('Using driver: '.$translator->driver::class);

        $imported = $translator->importTranslations($path);

        info('Translations sucessfully imported.');

        return self::SUCCESS;
    }
}
