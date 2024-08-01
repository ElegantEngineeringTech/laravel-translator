<?php

namespace Elegantly\Translator\Tests;

use Elegantly\Translator\TranslatorServiceProvider;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function getExcludedPaths(): array
    {
        return [
            'ignored',
            'ignored.blade.php',
        ];
    }

    public function getAppPath(): string
    {
        return __DIR__.'/src/app';
    }

    public function getResourcesPath(): string
    {
        return __DIR__.'/src/resources';
    }

    public function getStorage(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => __DIR__.'/src/lang',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Elegantly\\Translator\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            TranslatorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-translator_table.php.stub';
        $migration->up();
        */
    }
}
