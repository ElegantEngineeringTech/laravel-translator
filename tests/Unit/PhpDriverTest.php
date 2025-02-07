<?php

use Elegantly\Translator\Drivers\PhpDriver;

it('transform an array into a file', function () {
    $driver = new PhpDriver(
        storage: Storage::fake()
    );

    $values = [
        'Login' => 'Login',
        'Don\'t have an account?' => 'Don\'t have an account?',
        'nested' => [
            'Don\'t have an account?' => 'Don\'t have an account?',
        ],
    ];

    expect($driver->toFile($values))->toBe(
        "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'Login' => 'Login',\n    'Don\'t have an account?' => 'Don\'t have an account?',\n    'nested' => [\n        'Don\'t have an account?' => 'Don\'t have an account?',\n    ],\n];\n"
    );
});
