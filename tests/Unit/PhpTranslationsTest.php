<?php

use Elegantly\Translator\Collections\PhpTranslations;

it('sorts translations and nested translations', function () {
    $translations = new PhpTranslations(
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
    $translations = new PhpTranslations([
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

    $missingTranslations = $translations->diffTranslationsKeys(
        new PhpTranslations([
            'a' => 'text',
            'c' => [
                'b' => 'text',
            ],
            'd' => '',
            'e' => null,
            'f' => [],
        ])
    );

    expect($missingTranslations->toArray())->toBe([
        'b',
        'c.a',
        'd',
        'e',
        'f.a',
    ]);
});

it('filters (nested) translations using only', function () {
    $translations = new PhpTranslations([
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

    expect(
        $translations->only(['a', 'c.a', 'd'])->toArray()
    )->toBe([
        'a' => 'text',
        'c' => [
            'a' => 'text',
        ],
        'd' => 'text',
    ]);
});
