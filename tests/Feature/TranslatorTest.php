<?php

use Elegantly\Translator\Services\SearchCode\PhpParserService;
use Elegantly\Translator\Translator;

it('gets locales', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
    );

    expect($translator->getLocales())->toBe(['en', 'fr']);
});

it('gets namespaces', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
    );

    expect($translator->getNamespaces('fr'))->toBe(['messages']);
});

it('sorts and saves nested translations', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
    );

    $translator->sortTranslations('fr', 'messages');

    expect(
        $translator->getTranslations('fr', 'messages')->toArray()
    )->toBe([
        'add' => 'Ajouter',
        'dummy' => [
            'class' => 'class factice',
            'component' => 'composant factice',
            'nested' => [
                'used',
                'as',
                'array',
            ],
            'view' => 'vue factice',
        ],
        'empty' => 'Vide',
        'hello' => 'Bonjour',
        'home' => [
            'end' => 'Fin',
            'missing' => 'Absent',
            'title' => 'Titre',
        ],
        'missing' => 'Absent',

    ]);
});

it('finds missing translations', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
    );

    $missing = $translator->getMissingTranslations(
        referenceLocale: 'fr',
        targetLocale: 'en',
        namespace: 'messages'
    );

    expect($missing)->toBe([
        'home.missing',
        'empty',
        'missing',
    ]);
});

it('finds all missing translations', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
    );

    $missing = $translator->getAllMissingTranslations(
        referenceLocale: 'fr',
    );

    expect($missing)->toBe([
        'en' => [
            'messages' => [
                'home.missing',
                'empty',
                'missing',
            ],
        ],
    ]);
});

it('finds dead translations in a namespace', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
        searchcodeService: new PhpParserService(
            paths: [
                $this->getAppPath(),
                $this->getResourcesPath(),
            ],
            excludedPaths: $this->getExcludedPaths()
        )
    );

    $dead = $translator->getDeadTranslations(
        locale: 'fr',
        namespace: 'messages'
    );

    expect($dead)->toBe([
        'hello',
        'add',
        'home.title',
        'home.end',
        'home.missing',
        'empty',
        'missing',
    ]);
});

it('ignore dead translations', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
        searchcodeService: new PhpParserService(
            paths: [
                $this->getAppPath(),
                $this->getResourcesPath(),
            ],
            excludedPaths: $this->getExcludedPaths()
        )
    );

    $dead = $translator->getDeadTranslations(
        locale: 'fr',
        namespace: 'messages',
        ignore: ['messages.home', 'messages.empty']
    );

    expect($dead)->toBe([
        'hello',
        'add',
        'missing',
    ]);
});

it('finds all dead translations', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
        searchcodeService: new PhpParserService(
            paths: [
                $this->getAppPath(),
                $this->getResourcesPath(),
            ],
            excludedPaths: $this->getExcludedPaths()
        )
    );

    $deadTranslations = $translator->getAllDeadTranslations();

    expect($deadTranslations)->toBe([
        'en' => [
            'messages' => [
                'hello',
                'add',
                'home.title',
                'home.end',
                'empty',
            ],
        ],
        'fr' => [
            'messages' => [
                'hello',
                'add',
                'home.title',
                'home.end',
                'home.missing',
                'empty',
                'missing',
            ],
        ],
    ]);
});

it('sets translations', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
    );

    $translator->setTranslations('en', 'messages', [
        'missing' => 'Missing',
        'empty' => 'Empty',
    ]);

    $translations = $translator->getTranslations('en', 'messages');

    expect(
        $translations->toArray()
    )->toMatchArray([
        'missing' => 'Missing',
        'empty' => 'Empty',
    ]);
});

it('deletes translations', function () {
    $translator = new Translator(
        storage: $this->getStorage(),
    );

    $translations = $translator->getTranslations('en', 'messages');

    expect($translations->get('hello'))->not->toBe(null);
    expect($translations->get('home.title'))->not->toBe(null);

    $translator->deleteTranslations('en', 'messages', [
        'hello',
        'home.title',
    ]);

    $translations = $translator->getTranslations('en', 'messages');

    expect($translations->get('hello'))->toBe(null);
    expect($translations->get('home.title'))->toBe(null);
});
