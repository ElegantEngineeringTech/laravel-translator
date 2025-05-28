<?php

declare(strict_types=1);

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

class AddLocaleCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:add-locale {locale} {source} {--translate} {--driver=}';

    public $description = 'Add a new locale with all keys';

    public function handle(): int
    {
        /** @var string $locale */
        $locale = $this->argument('locale');
        /** @var string $source */
        $source = $this->argument('source');
        $translate = (bool) $this->option('translate');

        $translator = $this->getTranslator();

        $locales = $translator->getLocales();

        intro('Using driver: '.$translator->driver::class);

        if (in_array($locale, $locales)) {
            info("{$locale} already exists.");

            return self::SUCCESS;
        }

        $translations = $translator->getTranslations($source);

        $count = $translations->count();

        $translator->saveTranslations(
            $locale,
            $translations->map(fn () => null)
        );

        info("{$source} added with {$count} keys.");

        if ($translate) {
            $translated = spin(function () use ($translator, $source, $locale, $translations) {

                return $translator->translateTranslations(
                    source: $source,
                    target: $locale,
                    keys: $translations->dot()->keys()->toArray()
                );

            }, "Translating the {$count} missing translations from '{$source}' to '{$locale}'");

            table(
                headers: ['Key', "Source {$source}", "Target {$locale}"],
                rows: $translated
                    ->dot()
                    ->map(function ($value, $key) use ($translations) {
                        return [
                            (string) $key,
                            (string) str($translations->getString($key))->limit(25),
                            (string) str((string) $value)->limit(25),
                        ];
                    })->toArray()
            );
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    public function promptForMissingArgumentsUsing(): array
    {
        return [
            'locale' => function () {
                return text(
                    label: 'What locale would you like to add?',
                    hint: implode(', ', $this->getLocales()).' already existen.',
                    required: true,
                );
            },
            'source' => function () {
                return select(
                    label: 'What is the locale of reference?',
                    options: $this->getLocales(),
                    default: config('app.locale'),
                    required: true,
                );
            },
        ];
    }
}
