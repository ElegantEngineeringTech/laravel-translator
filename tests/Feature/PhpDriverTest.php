<?php

use Elegantly\Translator\Translator;

it('gets locales', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    expect($translator->getLocales())->toBe(['en', 'fr']);
});

it('gets translations', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    $translations = $translator->getTranslations('fr');

    expect($translations->toArray())->toBe([
        'messages.hello' => 'Bonjour',
        'messages.add' => 'Ajouter',
        'messages.home.title' => 'Titre',
        'messages.home.end' => 'Fin',
        'messages.home.missing' => 'Absent',
        'messages.empty' => 'Vide',
        'messages.missing' => 'Absent',
        'messages.dummy.class' => 'class factice',
        'messages.dummy.component' => 'composant factice',
        'messages.dummy.view' => 'vue factice',
        'messages.dummy.nested.0' => 'used',
        'messages.dummy.nested.1' => 'as',
        'messages.dummy.nested.2' => 'array',
    ]);
});

it('gets undefined translations', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $keys = $translator->getMissingTranslations('fr');

    expect($keys)->toBe([
        'This one is used.' => [
            'count' => 1,
            'files' => [
                0 => '/tests/src/resources/views/dummy-view.blade.php',
            ],
        ],
    ]);

});

it('gets dead translations', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $dead = $translator->getDeadTranslations('fr');

    expect($dead->keys()->toArray())->toBe([
        'messages.hello',
        'messages.add',
        'messages.home.title',
        'messages.home.end',
        'messages.home.missing',
        'messages.empty',
        'messages.missing',
    ]);

});

it('gets missing translations', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
        searchcodeService: $this->getSearchCodeService()
    );

    $untranlated = $translator->getUntranslatedTranslations(
        source: 'fr',
        target: 'en'
    );

    expect($untranlated->toArray())->toBe([
        'messages.home.missing' => 'Absent',
        'messages.empty' => 'Vide',
        'messages.missing' => 'Absent',
    ]);

});
