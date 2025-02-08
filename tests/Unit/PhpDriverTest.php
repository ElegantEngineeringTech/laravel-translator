<?php

declare(strict_types=1);

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

it('gets nested folder as subdrivers', function () {
    $driver = $this->getPhpDriver();

    $subDrivers = $driver->getSubDrivers();

    $subDriversKeys = collect($subDrivers)->map(fn ($driver) => $driver->getKey())->all();

    expect($subDriversKeys)->tobe([
        $driver->storage->path('sublang/'),
        $driver->storage->path('sublang/subsublang/'),
        $driver->storage->path('vendorlang/package/'),
    ]);
});
