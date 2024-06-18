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
