<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Exporter;

use Illuminate\Support\Arr;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class CsvExporterService implements ExporterInterface
{
    public function __construct()
    {
        //
    }

    public static function make(): self
    {
        return new self;
    }

    public function export(array $translationsByLocale, string $path): string
    {

        $writer = SimpleExcelWriter::create($path);

        /** @var string[] $locales */
        $locales = array_keys($translationsByLocale);

        $writer->addHeader(['key', ...$locales]);

        /** @var string[] $keys */
        $keys = collect($translationsByLocale)
            ->flatMap(fn ($translations) => $translations->keys())
            ->unique()
            ->all();

        foreach ($keys as $key) {
            $writer->addRow([
                'key' => $key,
                ...array_map(fn ($locale) => $translationsByLocale[$locale]->get($key), $locales),
            ]);
        }

        $writer->close();

        return $path;

    }

    public function import(string $path): array
    {

        /**
         * @var array<string, array<string, scalar>> $translationsByLocale
         */
        $translationsByLocale = [];

        $rows = SimpleExcelReader::create($path)->getRows();

        foreach ($rows as $row) {

            $key = Arr::pull($row, 'key');

            foreach ($row as $locale => $value) {

                $translationsByLocale[$locale] = [
                    ...($translationsByLocale[$locale] ?? []),
                    $key => $value,
                ];

            }

        }

        return $translationsByLocale;

    }
}
