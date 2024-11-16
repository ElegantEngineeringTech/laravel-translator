<?php

use Elegantly\Translator\Services\SearchCode\PhpParserService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

it('finds all occurences of __ in php code', function (string $code) {
    $results = PhpParserService::scanCode($code);

    expect($results)->toHaveLength(1);
})->with([
    "<?php __('messages.dummy.class');",
    "<?php trans('messages.dummy.class');",
    "<?php trans_choice('messages.dummy.class', 1);",
    "<?php __('messages.dummy.class', []);",
    "<?php __('messages.dummy.class', [], 'en');",
    "<?php __(key: 'messages.dummy.class');",
    "<?php __(key: 'messages.dummy.class', replace: [], locale: 'en');",
    "<?php \Illuminate\Support\Facades\Lang::get(key: 'messages.dummy.class');",
    "<?php \Illuminate\Support\Facades\Lang::has(key: 'messages.dummy.class');",
    "<?php \Illuminate\Support\Facades\Lang::hasForLocale(key: 'messages.dummy.class');",
    "<?php \Illuminate\Support\Facades\Lang::choice(key: 'messages.dummy.class');",
    "<?php app('translator')->get('messages.dummy.class');",
    "<?php app('translator')->has('messages.dummy.class');",
    "<?php app('translator')->hasForLocale('messages.dummy.class');",
    "<?php app('translator')->choice('messages.dummy.class');",
]);

it('ignore package translations keys in php code', function (string $code) {
    $results = PhpParserService::scanCode($code);

    expect($results)->toHaveLength(0);
})->with([
    "<?php __('package::messages.dummy.class');",
    "<?php __('package-name::messages.dummy.class');",
    "<?php __('package-Name::messages.dummy.class');",
    "<?php __('Package_Name::messages.dummy.class');",
    "<?php trans('package::messages.dummy.class');",
    "<?php trans_choice('package::messages.dummy.class', 1);",
]);

it('finds all occurences of __ in blade code', function (string $code) {
    $results = PhpParserService::scanCode(Blade::compileString($code));

    expect($results)->toHaveLength(1);
})->with([
    "{{ __('messages.dummy.class') }}",
    "{{ trans('messages.dummy.class') }}",
    "{{ \Illuminate\Support\Facades\Lang::get('messages.dummy.class') }}",
    "@lang('messages.dummy.class')",
]);

it('gets all the translations keys grouped by files', function () {

    $appPath = $this->getAppPath();
    $resourcesPath = $this->getResourcesPath();

    $service = new PhpParserService(
        paths: [
            $appPath,
            $resourcesPath,
        ],
        excludedPaths: $this->getExcludedPaths()
    );

    expect($service->translationsByFiles())->toBe([
        "{$appPath}/DummyClass.php" => [
            'messages.dummy.class',
        ],
        "{$resourcesPath}/components/dummy-component.blade.php" => [
            'messages.dummy.component',
            'messages.dummy.view',
        ],
        "{$resourcesPath}/views/dummy-view.blade.php" => [
            'This one is used.',
            'messages.dummy.nested',
            'messages.dummy.view',
            'messages.dummy.view',
        ],
    ]);
})->skipOnWindows();

it('gets all the files grouped by translations', function () {

    $appPath = $this->getAppPath();
    $resourcesPath = $this->getResourcesPath();

    $service = new PhpParserService(
        paths: [
            $appPath,
            $resourcesPath,
        ],
        excludedPaths: $this->getExcludedPaths()
    );

    expect($service->filesByTranslations())->toBe([
        'This one is used.' => [
            'count' => 1,
            'files' => [
                "{$resourcesPath}/views/dummy-view.blade.php",
            ],
        ],
        'messages.dummy.class' => [
            'count' => 1,
            'files' => [
                "{$appPath}/DummyClass.php",
            ],
        ],
        'messages.dummy.component' => [
            'count' => 1,
            'files' => [
                "{$resourcesPath}/components/dummy-component.blade.php",
            ],
        ],
        'messages.dummy.nested' => [
            'count' => 1,
            'files' => [
                "{$resourcesPath}/views/dummy-view.blade.php",
            ],
        ],
        'messages.dummy.view' => [
            'count' => 3,
            'files' => [
                "{$resourcesPath}/components/dummy-component.blade.php",
                "{$resourcesPath}/views/dummy-view.blade.php",
            ],
        ],

    ]);
})->skipOnWindows();

it('caches results from files', function () {

    $appPath = $this->getAppPath();
    $resourcesPath = $this->getResourcesPath();

    $service = new PhpParserService(
        paths: [
            $appPath,
            $resourcesPath,
        ],
        excludedPaths: $this->getExcludedPaths(),
        cacheStorage: Storage::fake('cache')
    );

    $service->cache->put("{$appPath}/DummyClass.php", [
        'messages.dummy.class',
    ]);

    $result = $service->cache->get("{$appPath}/DummyClass.php");

    expect($result['created_at'])->toBeInt();

    expect($result['translations'])->toBe([
        'messages.dummy.class',
    ]);
})->skipOnWindows();
