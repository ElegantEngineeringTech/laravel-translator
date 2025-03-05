<?php

declare(strict_types=1);

use Elegantly\Translator\Services\Exporter\CsvExporterService;
use Elegantly\Translator\Translator;
use Illuminate\Support\Facades\Storage;

it('exports all locales and keys to a csv file', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
        exporter: new CsvExporterService
    );

    $storage = Storage::fake('csv');

    $file = 'export.csv';

    $path = $storage->path($file);

    $translator->exportTranslations($path);

    Storage::disk('csv')->assertExists($file);
});

it('imports all locales and keys from a csv file', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
        exporter: new CsvExporterService
    );

    $translations = $translator->exporter->import(dirname(__DIR__).'/files/import.csv');

    expect($translations)->toBe([
        'en' => [
            'messages.hello' => 'Hello',
            'messages.add' => 'Add',
        ],
        'fr' => [
            'messages.hello' => 'Bonjour',
            'messages.add' => 'Ajouter',
        ],
    ]);

});
