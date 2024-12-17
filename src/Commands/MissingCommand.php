<?php

namespace Elegantly\Translator\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laravel\Prompts\Progress;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class MissingCommand extends TranslatorCommand implements PromptsForMissingInput
{
    public $signature = 'translator:missing {locale} {--sync : Add the missing keys to your driver} {--driver=}';

    public $description = 'Display all the translation keys found in the codebase but not in the driver.';

    public function formatPath(string $path): string
    {
        return (string) str($path)->after(base_path());
    }

    public function handle(): int
    {
        $locale = $this->argument('locale');
        $sync = (bool) $this->option('sync');

        /** @var null|Progress<int> $progress */
        $progress = null;

        $translator = $this->getTranslator();

        $missing = $translator->getMissingTranslations(
            $locale,
            start: function (int $total) use (&$progress) {
                $progress = new Progress('Scanning your codebase', $total);
                $progress->start();
            },
            progress: function (string $path) use (&$progress) {
                $progress?->hint($this->formatPath($path));
                $progress?->advance();
            },
            end: fn () => $progress?->finish(),
        );

        $count = count($missing);

        intro('Using driver: '.$translator->driver::class);

        note("{$count} missing keys detected.");

        table(
            headers: ['Key', 'Count', 'Files'],
            rows: collect($missing)
                ->map(function ($value, $key) {
                    return [
                        (string) str($key)->limit(20),
                        (string) $value['count'],
                        collect($value['files'])
                            ->map(fn ($file) => $this->formatPath($file))
                            ->join("\n"),
                    ];
                })->values()->all()
        );

        if ($sync) {

            $translator->setTranslations(
                locale: $locale,
                values: array_map(fn () => null, $missing)
            );

            info("{$count} missing keys added to the driver.");

        }

        return self::SUCCESS;
    }
}
