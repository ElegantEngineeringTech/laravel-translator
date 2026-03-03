<?php

declare(strict_types=1);

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laravel\Prompts\Progress;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;

class TranslateCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:translate {source} {target} {--force} {--chunk=10} {--driver=}';

    public $description = 'Translate all the translation keys to the target locale.';

    public function handle(): int
    {
        /** @var string $source */
        $source = $this->argument('source');
        /** @var string $target */
        $target = $this->argument('target');
        $force = (bool) $this->option('force');
        $chunkSize = (int) $this->option('chunk');

        $translator = $this->getTranslator();

        intro('Using driver: '.$translator->driver::class);

        $translations = $force ? $translator->getTranslations($source)->dot() : $translator->getUntranslatedTranslations($source, $target)->dot();

        $count = $translations->count();

        if ($count < 1) {
            info("{$count} keys to translate.");

            return self::SUCCESS;
        }

        $progress = new Progress(
            "Translating {$source} to {$target} (chunk size: {$chunkSize}).",
            $count
        );

        $chunks = $translations->chunk($chunkSize);

        foreach ($chunks as $chunk) {
            $translator->translateTranslations(
                source: $source,
                target: $target,
                keys: $chunk->keys()->all()
            );

            $progress->advance($chunk->count());
        }

        $progress->finish();

        return self::SUCCESS;
    }
}
