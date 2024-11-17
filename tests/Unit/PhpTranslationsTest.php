<?php

use Elegantly\Translator\Collections\PhpTranslations;

it('gets the right translation value', function () {

    $translations = new PhpTranslations([
        'a.b' => 'b_value',
        'c' => 'c_value',
        'c.d.0' => '0_value',
        'c.d.1' => '1_value',
        'e' => '',
        'f.g' => '',
        'h.i.j.k' => 'k_value',
        'h.i.j.l' => 'l_value',
    ]);

    expect($translations->get('c'))->toBe('c_value');
    expect($translations->get('a.b'))->toBe('b_value');
    expect($translations->get('a'))->toBe([
        'b' => 'b_value',
    ]);
    expect($translations->get('c.d'))->toBe([
        0 => '0_value',
        1 => '1_value',
    ]);
    expect($translations->get('c.d.1'))->toBe('1_value');
    expect($translations->get('e'))->toBe('');
    expect($translations->get('f.g'))->toBe('');
    expect($translations->get('h'))->toBe([
        'i' => [
            'j' => [
                'k' => 'k_value',
                'l' => 'l_value',
            ],
        ],
    ]);

});

it('compare two translation keys', function ($a, $b, $expected) {

    expect(PhpTranslations::areTranslationKeysEqual($a, $b))->toBe($expected);

})->with([
    ['a', 'a', true],
    ['a', 'b', false],
    ['a', 'a.b', false],
    ['a.b', 'a.b', true],
    ['a.b', 'a', true],
    ['a.b', 'a.b.c', false],
]);

it('check existance of translation value', function ($has, $expected) {

    $translations = new PhpTranslations([
        'a.b' => 'b_value',
        'c' => 'c_value',
    ]);

    expect($translations->has($has))->toBe($expected);

})->with([
    ['a', true],
    ['a.b', true],
    ['c', true],
    ['a.b.c', false],
    ['a.b.c.d', false],
    ['e', false],
]);

it('retreives values except for some translation keys', function ($except, $expected) {

    $translations = new PhpTranslations([
        'a.b' => 'b_value',
        'c' => 'c_value',
    ]);

    expect($translations->except($except)->toArray())->toBe($expected);

})->with([
    [
        ['a'],
        ['c' => 'c_value'],
    ],
    [
        ['a.b'],
        ['c' => 'c_value'],
    ],
    [
        ['c'],
        ['a.b' => 'b_value'],
    ],
    [
        ['a.b.c'],
        [
            'a.b' => 'b_value',
            'c' => 'c_value',
        ],
    ],
]);

it('retreives only the specified translation keys', function ($only, $expected) {

    $translations = new PhpTranslations([
        'a.b' => 'b_value',
        'c' => 'c_value',
    ]);

    expect($translations->only($only)->toArray())->toBe($expected);

})->with([
    [
        ['a'],
        ['a.b' => 'b_value'],
    ],
    [
        ['a.b'],
        ['a.b' => 'b_value'],
    ],
    [
        ['c'],
        ['c' => 'c_value'],
    ],
    [
        ['a.b.c'],
        [],
    ],
]);
