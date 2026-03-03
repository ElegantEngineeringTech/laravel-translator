<?php

declare(strict_types=1);

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\select;
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

        info("{$locale} added with {$count} keys.");

        if ($translate) {

            return Artisan::call(TranslateCommand::class, [
                'source' => $source,
                'target' => $locale,
                '--driver' => $this->getDriverName(),
            ]);
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
