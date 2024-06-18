<?php

use Elegantly\Translator\Translations;

it('sorts translations and nested translations', function () {
    $translations = new Translations(
        items: [
            'c' => null,
            'b' => null,
            'a' => [
                'b' => null,
                'z' => [
                    'b' => null,
                    'a' => null,
                ],
                'a' => null,
            ],
            'd' => null,
        ],
    );

    expect(
        $translations->sortNatural()->toArray()
    )->toBe([
        'a' => [
            'a' => null,
            'b' => null,
            'z' => [
                'a' => null,
                'b' => null,
            ],
        ],
        'b' => null,
        'c' => null,
        'd' => null,
    ]);
});

it('finds missing (nested) translations in another collections', function () {
    $translations = new Translations([
        'a' => 'text',
        'b' => 'text',
        'c' => [
            'a' => 'text',
            'b' => 'text',
        ],
        'd' => 'text',
        'e' => 'text',
        'f' => [
            'a' => 'text',
        ],
    ]);

    $missingTranslations = $translations->getMissingTranslationsIn(
        new Translations([
            'a' => 'text',
            'c' => [
                'b' => 'text',
            ],
            'd' => '',
            'e' => null,
            'f' => [],
        ])
    );

    expect($missingTranslations)->toBe([
        'b',
        'c.a',
        'd',
        'e',
        'f.a',
    ]);
});
