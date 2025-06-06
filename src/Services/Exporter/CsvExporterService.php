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
            ->flatMap(fn ($translations) => $translations->dot()->keys()->all())
            ->unique()
            ->all();

        foreach ($keys as $key) {

            $values = array_map(function ($locale) use ($translationsByLocale, $key) {

                $value = $translationsByLocale[$locale]->get($key);

                if (is_array($value)) {
                    return null;
                }

                return $value;
            }, $locales);

            $writer->addRow([
                'key' => $key,
                ...$values,
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

                if (! array_key_exists($locale, $translationsByLocale)) {
                    $translationsByLocale[$locale] = [];
                }

                if ($value) {
                    $translationsByLocale[$locale][$key] = $value;
                }

            }

        }

        return $translationsByLocale;

    }
}
