<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\select;

/**
 * Display translations strings found in codebase but not in a locale
 */
class UndefinedCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:undefined {source}';

    public $description = 'Display all the translation strings not found in a locale.';

    public function handle(): int
    {
        $source = $this->argument('source');

        $values = Translator::getUndefinedTranslations($source);

        return self::SUCCESS;
    }

    public function promptForMissingArgumentsUsing()
    {
        return [
            'source' => function () {
                return select(
                    label: 'What is the locale of reference?',
                    options: Translator::getLocales(),
                    default: config('app.locale'),
                    required: true,
                );
            },
        ];
    }
}
