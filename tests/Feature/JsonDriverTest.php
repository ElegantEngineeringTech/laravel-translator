<?php

declare(strict_types=1);

use Elegantly\Translator\Translator;

it('gets locales', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
    );

    expect($translator->getLocales())->toBe(['fr', 'it']);
});

it('gets translations', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
    );

    $translations = $translator->getTranslations('fr');

    expect($translations->toArray())->toBe([
        'All rights reserved.' => 'Tous droits réservés.',
        'This one is untranslated' => 'Celui-ci est manquant',
        'This one is dead' => 'Celui-ci est mort',
    ]);
});

it('gets missing translations', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $keys = $translator->getMissingTranslations('fr');

    expect($keys)->toBe([
        'This one is missing' => [
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
        'messages.title' => [
            'count' => 1,
            'files' => [
                $this->formatPath($this->getAppPath().'/Foo.php'),
            ],
        ],
        'users/account.title' => [
            'count' => 1,
            'files' => [
                $this->formatPath($this->getResourcesPath().'/components/users/account.blade.php'),
            ],
        ],
    ]);

});

it('gets dead translations', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $dead = $translator->getDeadTranslations('fr');

    expect($dead->keys())->toBe([
        'This one is dead',
    ]);

});

it('gets untranslated translations', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $keys = $translator->getUntranslatedTranslations(
        source: 'fr',
        target: 'it'
    );

    expect($keys->toArray())->toBe([
        'This one is untranslated' => 'Celui-ci est manquant',
    ]);

});
