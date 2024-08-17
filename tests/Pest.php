<?php

use Elegantly\Translator\Caches\SearchCodeCache;
use Elegantly\Translator\Tests\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    /** @var Filesystem $storage */
    $storage = $this->getStorage();

    foreach ($storage->allFiles() as $file) {
        if (str($file)->endsWith('.php')) {
            $storage->copy(
                from: $file,
                to: str_replace('.php', '.php.stub', $file),
            );
        } elseif (str($file)->endsWith('.json')) {
            $storage->copy(
                from: $file,
                to: str_replace('.json', '.json.stub', $file),
            );
        }
    }
})->in('Feature');

uses()->afterEach(function () {
    /** @var Filesystem $storage */
    $storage = $this->getStorage();

    foreach ($storage->allFiles() as $file) {
        if (str($file)->endsWith('.php.stub')) {
            $storage->move(
                from: $file,
                to: str_replace('.php.stub', '.php', $file),
            );
        } elseif (str($file)->endsWith('.json.stub')) {
            $storage->move(
                from: $file,
                to: str_replace('.json.stub', '.json', $file),
            );
        }
    }

    $cache = new SearchCodeCache(
        storage: Storage::fake('cache')
    );

    $cache->flush();
})->in('Feature');
