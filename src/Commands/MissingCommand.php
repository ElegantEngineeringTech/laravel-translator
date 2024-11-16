<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

/**
 * Display translations strings found in codebase but not in a locale
 */
class MissingCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:missing {source?} {target?}';

    public $description = 'Display all the translation strings not found in a locale.';

    public function handle(): int
    {

        $source = $this->argument('source');
        $target = $this->argument('target');

        return self::SUCCESS;
    }

    public function promptForMissingArgumentsUsing()
    {
        return [
        ];
    }
}
