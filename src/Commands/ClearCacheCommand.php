<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;

class ClearCacheCommand extends Command
{
    public $signature = 'translator:clear-cache';

    public $description = 'Clear the Translator cache.';

    public function handle(): int
    {
        Translator::clearCache();

        info('Cache cleared');

        return self::SUCCESS;
    }
}
