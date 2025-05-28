<?php

declare(strict_types=1);

use Elegantly\Translator\Collections\PhpTranslations;

$translations = new PhpTranslations([
    'a' => [
        'b' => 'b_value',
    ],
    'c' => [
        'd' => ['0_value', '1_value'],
    ],
    'e' => '',
    'f' => [
        'g' => '',
    ],
    'h' => [
        'i' => [
            'j' => [
                'k' => 'k_value',
                'l' => 'l_value',
            ],
        ],
    ],
]);

it('gets the right translation value', function () use ($translations) {

    expect($translations->get('c'))->toBe([
        'd' => ['0_value', '1_value'],
    ]);
    expect($translations->get('a.b'))->toBe('b_value');
    expect($translations->get('a'))->toBe([
        'b' => 'b_value',
    ]);
    expect($translations->get('c.d'))->toBe(['0_value', '1_value']);
    expect($translations->get('c.d.1'))->toBe('1_value');
    expect($translations->get('e'))->toBe('');
    expect($translations->get('f.g'))->toBe('');
    expect($translations->get('h.i.j.l'))->toBe('l_value');
    expect($translations->get('h'))->toBe([
        'i' => [
            'j' => [
                'k' => 'k_value',
                'l' => 'l_value',
            ],
        ],
    ]);

});

it('checks existence of translation value', function ($key, $expected) use ($translations) {

    expect($translations->has($key))->toBe($expected);

})->with([
    ['a', true],
    ['a.b', true],
    ['a.b.c', false],
    ['c', true],
    ['c.d.0', true],
    ['c.d.1', true],
    ['c.d.2', false],
    ['e', true],
    ['f', true],
    ['f.g', true],
    ['f.g.h', false],
    ['h.i.j.k', true],
]);

it('filters values', function ($callback, $expected) use ($translations) {

    $filtered = $translations->filter($callback);

    expect($filtered->toArray())->toBe($expected);

})->with([
    [
        fn ($value, $key) => blank($value),
        [
            'e' => '',
            'f' => [
                'g' => '',
            ],
        ],
    ],
    [
        fn ($value, $key) => ! blank($value),
        [
            'a' => [
                'b' => 'b_value',
            ],
            'c' => [
                'd' => ['0_value', '1_value'],
            ],
            'h' => [
                'i' => [
                    'j' => [
                        'k' => 'k_value',
                        'l' => 'l_value',
                    ],
                ],
            ],
        ],
    ],
]);

it('retreives values except for some translation keys', function ($except, $expected) {

    $translations = new PhpTranslations([
        'a' => [
            'b' => 'b_value',
        ],
        'c' => [
            'd' => ['0_value', '1_value'],
        ],
        'h' => [
            'i' => [
                'j' => [
                    'k' => 'k_value',
                    'l' => 'l_value',
                ],
            ],
        ],
    ]);

    expect($translations->except($except)->toArray())->toBe($expected);

})->with([
    [
        ['a'],
        [
            'c' => [
                'd' => ['0_value', '1_value'],
            ],
            'h' => [
                'i' => [
                    'j' => [
                        'k' => 'k_value',
                        'l' => 'l_value',
                    ],
                ],
            ],
        ],
    ],
    [
        ['c.d.0'],
        [
            'a' => [
                'b' => 'b_value',
            ],
            'c' => [
                'd' => [1 => '1_value'],
            ],
            'h' => [
                'i' => [
                    'j' => [
                        'k' => 'k_value',
                        'l' => 'l_value',
                    ],
                ],
            ],
        ],
    ],
    [
        ['a.b'],
        [
            'c' => [
                'd' => ['0_value', '1_value'],
            ],
            'h' => [
                'i' => [
                    'j' => [
                        'k' => 'k_value',
                        'l' => 'l_value',
                    ],
                ],
            ],
        ],
    ],
    [
        ['h.i.j.l'],
        [
            'a' => [
                'b' => 'b_value',
            ],
            'c' => [
                'd' => ['0_value', '1_value'],
            ],
            'h' => [
                'i' => [
                    'j' => [
                        'k' => 'k_value',
                    ],
                ],
            ],
        ],
    ],
    [
        ['a', 'c.d', 'h.i.j'],
        [],
    ],
]);

it('retreives only the specified translation keys', function ($only, $expected) use ($translations) {

    expect($translations->only($only)->toArray())->toBe($expected);

})->with([
    [
        ['a'],
        [
            'a' => [
                'b' => 'b_value',
            ],
        ],
    ],
    [
        ['h.i.j.k'],
        [
            'h' => [
                'i' => [
                    'j' => [
                        'k' => 'k_value',
                    ],
                ],
            ],
        ],
    ],
]);

it('sorts keys in ascending order', function () {

    $translations = new PhpTranslations([
        'z' => [
            'y' => 'y_value',
            'x' => 'y_value',
        ],
        'w' => 'w_value',
        'a' => [
            'f' => 'f_value',
            'b' => [
                'e' => 'e_value',
                'c' => 'c_value',
                'd' => 'd_value',
            ],
        ],
        'v' => 'v_value',
    ]);

    expect($translations->sortKeys(descending: false)->all())->toBe([
        'a' => [
            'b' => [
                'c' => 'c_value',
                'd' => 'd_value',
                'e' => 'e_value',
            ],
            'f' => 'f_value',
        ],
        'v' => 'v_value',
        'w' => 'w_value',
        'z' => [
            'x' => 'y_value',
            'y' => 'y_value',
        ],
    ]);

});

it('sorts keys in descending order', function () {

    $translations = new PhpTranslations([
        'z' => [
            'y' => 'y_value',
            'x' => 'y_value',
        ],
        'w' => 'w_value',
        'a' => [
            'f' => 'f_value',
            'b' => [
                'e' => 'e_value',
                'c' => 'c_value',
                'd' => 'd_value',
            ],
        ],
        'v' => 'v_value',
    ]);

    expect($translations->sortKeys(descending: true)->all())->toBe([
        'z' => [
            'y' => 'y_value',
            'x' => 'y_value',
        ],
        'w' => 'w_value',
        'v' => 'v_value',
        'a' => [
            'f' => 'f_value',
            'b' => [
                'e' => 'e_value',
                'd' => 'd_value',
                'c' => 'c_value',
            ],
        ],
    ]);

});

it('encodes dot to unicode', function () {

    $translations = PhpTranslations::prepareTranslations([
        'This key contains a dot. In the middle' => [
            'And it.has children.' => 'And it has children.',
        ],
    ]);

    expect($translations)->toBe([
        'This key contains a dot&#46; In the middle' => [
            'And it&#46;has children&#46;' => 'And it has children.',
        ],
    ]);

});

it('decodes dot from unicode', function () {

    $translations = PhpTranslations::unprepareTranslations([
        'This key contains a dot&#46; In the middle' => [
            'And it&#46;has children&#46;' => 'And it has children.',
        ],
    ]);

    expect($translations)->toBe([
        'This key contains a dot. In the middle' => [
            'And it.has children.' => 'And it has children.',
        ],
    ]);

});
