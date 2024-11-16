<?php

use Elegantly\Translator\Collections\PhpTranslations;

it('gets the right translation value', function () {

    $translations = new PhpTranslations([
        'a.b' => 'b_value',
        'c' => 'c_value',
        'c.d.0' => '0_value',
        'c.d.1' => '1_value',
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

});
