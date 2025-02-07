<?php

declare(strict_types=1);

namespace Elegantly\Translator\Tests;

use Elegantly\Translator\Drivers\JsonDriver;
use Elegantly\Translator\Drivers\PhpDriver;
use Elegantly\Translator\Services\SearchCode\PhpParserService;
use Elegantly\Translator\Services\SearchCode\SearchCodeServiceInterface;
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

    public function formatPath(string $value): string
    {
        return implode(
            DIRECTORY_SEPARATOR,
            explode('/', $value)
        );
    }

    public function getAppPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, 'src', 'app']);
    }

    public function getResourcesPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, 'src', 'resources']);
    }

    public function getPhpDriver(): PhpDriver
    {
        return new PhpDriver(
            $this->getStorage()
        );
    }

    public function getJsonDriver(): JsonDriver
    {
        return new JsonDriver(
            $this->getStorage()
        );
    }

    public function getStorage(): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => __DIR__.'/src/lang',
        ]);
    }

    public function getSearchCodeService(): SearchCodeServiceInterface
    {
        return new PhpParserService(
            paths: [
                $this->getAppPath(),
                $this->getResourcesPath(),
            ],
            excludedPaths: $this->getExcludedPaths()
        );
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
