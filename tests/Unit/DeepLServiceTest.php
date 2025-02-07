<?php

declare(strict_types=1);

use Elegantly\Translator\Services\DeepLService;

it('translates using DeepL while preserving array keys', function () {
    $service = new DeepLService(
        key: 'REDACTED'
    );

    $translations = $service->translateAll([
        'home' => 'Maison',
        'pool' => 'Piscine',
        'see' => 'Voir',
    ], 'en');

    expect(array_keys($translations))->toBe([
        'home',
        'pool',
        'see',
    ]);
})->skip();
