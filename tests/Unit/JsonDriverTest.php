<?php

declare(strict_types=1);

it('gets nested folder as subdrivers', function () {
    $driver = $this->getJsonDriver();

    $subDrivers = $driver->getSubDrivers();

    $subDriversKeys = collect($subDrivers)->map(fn ($driver) => $driver->getKey())->all();

    expect($subDriversKeys)->tobe([
        $driver->storage->path($this->formatPath('sublang/')),
        $driver->storage->path($this->formatPath('sublang/subsublang/')),
        $driver->storage->path($this->formatPath('vendorlang/package/')),
    ]);
});
