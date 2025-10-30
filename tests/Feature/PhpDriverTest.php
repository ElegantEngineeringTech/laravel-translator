<?php

declare(strict_types=1);

use Elegantly\Translator\Translator;

it('gets locales from the directory', function () {
    $driver = $this->getPhpDriver();

    expect($driver->getLocales())->toBe(['en', 'fr', 'fr_CA', 'package']);
});

it('gets namespaces from the directory', function () {
    $driver = $this->getPhpDriver();

    expect($driver->getNamespaces('fr'))->toBe([
        'messages',
        'users/account',
    ]);
});

it('gets translations', function () {
    $driver = $this->getPhpDriver();

    $translations = $driver->getTranslations('fr');

    expect($translations->toArray())->toBe([
        'messages' => [
            'title' => 'Titre',
            'ignored' => 'ignoré',
            'nested' => [
                'title' => 'Sous-titre',
                'array' => ['Option 1', 'Option 2'],
                'untranslated' => 'Non traduit',
            ],
            'untranslated' => 'Non traduit',
        ],
        'users/account' => [
            'title' => 'Compte',
            'untranslated' => 'Non traduit',
        ],
    ]);
});

it('gets locales from the config', function () {
    config()->set('translator.locales', ['fr']);

    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    expect($translator->getLocales())->toBe(['fr']);
});

it('gets locales from the directory when the config is null', function () {
    config()->set('translator.locales', null);

    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    expect($translator->getLocales())->toBe(['en', 'fr', 'fr_CA', 'package']);
});

it('gets untranslated translations', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $untranlated = $translator->getUntranslatedTranslations(
        source: 'fr',
        target: 'en'
    );

    expect($untranlated->toArray())->toBe([
        'messages' => [
            'nested' => [
                'untranslated' => 'Non traduit',
            ],
            'untranslated' => 'Non traduit',
        ],
        'users/account' => [
            'untranslated' => 'Non traduit',
        ],
    ]);

});

it('gets missing translations', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $keys = $translator->getMissingTranslations('fr');

    expect($keys)->toBe([
        'All rights reserved.' => [
            'count' => 1,
            'files' => [
                $this->formatPath($this->getResourcesPath().'/views/json/foo.blade.php'),
            ],
        ],
        'This one is missing' => [
            'count' => 1,
            'files' => [
                $this->formatPath($this->getResourcesPath().'/views/json/foo.blade.php'),
            ],
        ],
        'This one is untranslated' => [
            'count' => 1,
            'files' => [
                $this->formatPath($this->getResourcesPath().'/views/json/foo.blade.php'),
            ],
        ],
        'messages.missing' => [
            'count' => 2,
            'files' => [
                $this->formatPath($this->getResourcesPath().'/views/foo.blade.php'),
            ],
        ],
        'messages.nested.missing' => [
            'count' => 1,
            'files' => [
                $this->formatPath($this->getResourcesPath().'/views/foo.blade.php'),
            ],
        ],
    ]);

});

it('gets dead translations', function () {

    $translator = new Translator(
        driver: $this->getPhpDriver(),
        ignoredTranslations: $this->getIgnoredTranslations(),
        searchcodeService: $this->getSearchCodeService()
    );

    $dead = $translator->getDeadTranslations('fr');

    expect($dead->dot()->keys()->toArray())->toBe([
        'messages.nested.title',
        'messages.nested.array.0',
        'messages.nested.array.1',
        'messages.nested.untranslated',
        'messages.untranslated',
        'users/account.untranslated',
    ]);

});

it('replaces dot with unicode', function () {
    $driver = $this->getPhpDriver();

    $translations = $driver->getTranslations('fr_CA');

    expect($translations->get('dot'))->toBe([
        'with&#46;dot' => 'Avec . point',
        'nested' => [
            'with&#46;dot' => 'Avec . point',
        ],
    ]);

});

it('doesn\'t break keys with dot', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    $translations = $translator->getTranslations('fr_CA');

    $translator->saveTranslations('fr_CA', $translations);

    expect($translator->getTranslations('fr_CA')->get('dot'))->toBe([
        'with&#46;dot' => 'Avec . point',
        'nested' => [
            'with&#46;dot' => 'Avec . point',
        ],
    ]);

});

it('gets nested folder as subdrivers', function () {
    $driver = $this->getPhpDriver();

    $subDrivers = $driver->getSubDrivers();

    $subDriversKeys = array_map(
        fn ($driver) => $driver->getKey(),
        $subDrivers
    );

    expect($subDriversKeys)->toEqualCanonicalizing([
        $driver->storage->path($this->formatPath('en/')),
        $driver->storage->path($this->formatPath('fr/')),
        $driver->storage->path($this->formatPath('package/')),
    ]);
});
