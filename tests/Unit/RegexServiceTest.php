<?php

use Elegantly\Translator\Services\SearchCode\RegexService;

it('gets all the translations keys grouped by files', function () {

    $appPath = $this->getAppPath();
    $resourcesPath = $this->getResourcesPath();

    $service = new RegexService(
        paths: [
            $appPath,
            $resourcesPath,
        ],
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
            'messages.dummy.view',
            'messages.dummy.view',
        ],
    ]);
});

it('gets all the files grouped by translations', function () {

    $appPath = $this->getAppPath();
    $resourcesPath = $this->getResourcesPath();

    $service = new RegexService(
        paths: [
            $appPath,
            $resourcesPath,
        ],
    );

    expect($service->filesByTranslations())->toBe([
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
        'messages.dummy.view' => [
            'count' => 3,
            'files' => [
                "{$resourcesPath}/components/dummy-component.blade.php",
                "{$resourcesPath}/views/dummy-view.blade.php",
            ],
        ],
    ]);
});
