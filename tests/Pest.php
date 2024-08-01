<?php

use Elegantly\Translator\Tests\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    /** @var Filesystem $storage */
    $storage = $this->getStorage();

    foreach ($storage->allDirectories() as $locale) {
        foreach ($storage->allFiles($locale) as $file) {
            if (str($file)->endsWith('.php')) {
                $storage->copy(
                    from: $file,
                    to: str_replace('.php', '.php.stub', $file),
                );
            }
        }
    }
})->in('Feature');

uses()->afterEach(function () {
    /** @var Filesystem $storage */
    $storage = $this->getStorage();

    foreach ($storage->allDirectories() as $locale) {
        foreach ($storage->allFiles($locale) as $file) {
            if (str($file)->endsWith('.php.stub')) {
                $storage->move(
                    from: $file,
                    to: str_replace('.php.stub', '.php', $file),
                );
            }
        }
    }
})->in('Feature');
