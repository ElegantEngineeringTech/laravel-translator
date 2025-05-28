<?php

declare(strict_types=1);

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

/**
 * Display translations strings found in codebase but not in a locale
 */
class ProofreadCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:proofread {locale} {--driver=}';

    public $description = 'Fix grammar and synax of the translations strings in the specified locale.';

    public function handle(): int
    {
        /** @var string $locale */
        $locale = $this->argument('locale');

        $translator = $this->getTranslator();

        intro('Using driver: '.$translator->driver::class);

        $translations = $translator->getTranslations($locale);

        $proofread = spin(
            fn () => $translator->proofreadTranslations(
                locale: $locale,
                keys: $translations->dot()->keys()->all()
            ),
            'Proofreading the translation strings.'
        );

        table(
            headers: ['Key', 'Before', 'After'],
            rows: $translations
                ->dot()
                ->map(fn ($value, $key) => [
                    (string) $key,
                    (string) str((string) $value)->limit(25),
                    (string) str($proofread->getString($key))->limit(25),
                ])
                ->all()
        );

        return self::SUCCESS;
    }
}
