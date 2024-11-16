<?php

use Elegantly\Translator\Translator;

it('gets locales', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
    );

    expect($translator->getLocales())->toBe(['fr']);
});

it('gets translations', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
    );

    $translations = $translator->getTranslations('fr');

    expect($translations->toArray())->toBe([
        'All rights reserved.' => 'Tous droits réservés.',
        'This one is used.' => 'Celui-ci est utilisé.',
    ]);
});

it('gets undefined translations', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $keys = $translator->getUndefinedTranslations('fr');

    expect($keys)->toBe([
        'messages.dummy.class' => [
            'count' => 1,
            'files' => [
                0 => '/Users/quentingabriele/dev/laravel-translator/tests/src/app/DummyClass.php',
            ],
        ],
        'messages.dummy.component' => [
            'count' => 1,
            'files' => [
                0 => '/Users/quentingabriele/dev/laravel-translator/tests/src/resources/components/dummy-component.blade.php',
            ],
        ],
        'messages.dummy.nested' => [
            'count' => 1,
            'files' => [
                0 => '/Users/quentingabriele/dev/laravel-translator/tests/src/resources/views/dummy-view.blade.php',
            ],
        ],
        'messages.dummy.view' => [
            'count' => 3,
            'files' => [
                0 => '/Users/quentingabriele/dev/laravel-translator/tests/src/resources/components/dummy-component.blade.php',
                1 => '/Users/quentingabriele/dev/laravel-translator/tests/src/resources/views/dummy-view.blade.php',
            ],
        ],
    ]);

});

it('gets dead translations', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $keys = $translator->getDeadTranslations('fr');

    expect($keys)->toBe([
        'All rights reserved.',
    ]);

});

it('gets missing translations', function () {
    $translator = new Translator(
        driver: $this->getJsonDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $keys = $translator->getMissingTranslations(
        source: 'fr',
        target: 'en'
    );

    expect($keys)->toBe([
        'All rights reserved.',
        'This one is used.',
    ]);

});
