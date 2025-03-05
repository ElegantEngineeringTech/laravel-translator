<?php

declare(strict_types=1);

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;

/**
 * Display translations strings found in codebase but not in a locale
 */
class ExportCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:export {path} {--driver=}';

    public $description = 'Export all the translations in a file.';

    public function handle(): int
    {
        /** @var string $path */
        $path = $this->argument('path');

        $translator = $this->getTranslator();

        intro('Using driver: '.$translator->driver::class);

        $translator->exportTranslations($path);

        info("Translations sucessfully exported here: {$path}");

        return self::SUCCESS;
    }
}
