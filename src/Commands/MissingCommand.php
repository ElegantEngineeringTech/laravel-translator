<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\table;

class MissingCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:missing {locale} {--sync : Add the missing keys to your driver} {--driver=}';

    public $description = 'Display all the translation keys found in the codebase but not in the driver.';

    public function handle(): int
    {
        $locale = $this->argument('locale');
        $sync = (bool) $this->option('sync');

        $translator = $this->getTranslator();
        $missing = $translator->getMissingTranslations($locale);

        intro('Using driver: ' . $translator->driver::class);

        $missingCount = count($missing);

        if ($missingCount === 0) {
            $this->components->info('No missing keys found for locale: ' . $locale);
            return self::SUCCESS;
        }

        $this->components->info("{$missingCount} missing keys detected.");
        $this->newLine();

        if (!$sync) {
            table(
                headers: ['Key', 'Total Count', 'Files'],
                rows: $this->formatMissingKeys($missing)
            );
        }

        if ($sync) {
            $this->syncMissingKeysWithProgress($translator, $locale, $missing);
        }

        return self::SUCCESS;
    }

    /**
     * Format missing keys for display in the table.
     *
     * @param array $missing
     * @return array
     */
    private function formatMissingKeys(array $missing): array
    {
        return collect($missing)
            ->map(fn($value, $key) => [
                str($key)->limit(20)->value(),
                (string) $value['count'],
                implode("\n", $this->formatFiles($value['files'])),
            ])
            ->values()
            ->all();
    }

    /**
     * Format file paths for display.
     *
     * @param array $files
     * @return array
     */
    private function formatFiles(array $files): array
    {
        return array_map(
            fn($file) => str($file)->after(base_path())->value(),
            $files
        );
    }

    /**
     * Sync missing keys with the translator driver, showing a progress bar.
     *
     * @param object $translator
     * @param string $locale
     * @param array $missing
     * @return void
     */
    private function syncMissingKeysWithProgress($translator, string $locale, array $missing): void
    {
        $progress = progress(label: 'Syncing...', steps: count($missing));
        $progress->start();

        foreach ($missing as $key => $value) {
            $translator->setTranslation($locale, $key, null);
            $progress->advance();
        }

        $progress->finish();
        $this->components->info(count($missing) . ' missing keys have been synced.');
    }
}
