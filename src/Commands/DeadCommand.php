<?php

declare(strict_types=1);

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

/**
 * Display translations strings found in codebase but not in a locale
 */
class DeadCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:dead {locale} {--sync : Delete the dead keys from your driver} {--driver=}';

    public $description = 'Display all the translation keys not found in the codebase.';

    public function handle(): int
    {
        $locale = $this->argument('locale');
        $sync = (bool) $this->option('sync');

        $translator = $this->getTranslator();

        $dead = $translator->getDeadTranslations($locale);

        intro('Using driver: '.$translator->driver::class);

        note(count($dead).' dead translations keys detected.');

        table(
            headers: ['Key', "Translation {$locale}"],
            rows: $dead
                ->toBase()
                ->map(function ($value, $key) {
                    return [
                        $key,
                        str((string) $value)->limit(50)->value(),
                    ];
                })
                ->values()
                ->all()
        );

        if ($sync) {

            $translator->deleteTranslations(
                locale: $locale,
                keys: $dead->keys()->toArray()
            );

            note(count($dead).' dead translations deleted from the driver.');

        }

        return self::SUCCESS;
    }
}
