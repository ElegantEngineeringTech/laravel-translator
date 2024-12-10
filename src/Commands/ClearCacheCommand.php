<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Facades\Translator;
use Illuminate\Console\Command;

class ClearCacheCommand extends Command
{
    public $signature = 'translator:clear-cache';

    public $description = 'Clear the Translator cache.';

    public function handle(): int
    {
        Translator::clearCache();

        $this->components->info('Translator cache cleared.');

        return self::SUCCESS;
    }
}
