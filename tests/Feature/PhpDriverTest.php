<?php

declare(strict_types=1);

use Elegantly\Translator\Translator;

it('gets locales from the directory', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    expect($translator->getLocales())->toBe(['en', 'fr', 'fr_CA', 'pt_BR']);
});

it('gets locales from the config', function () {
    config()->set('translator.locales', ['fr']);

    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    expect($translator->getLocales())->toBe(['fr']);
});

it('gets locales from the config when null', function () {
    config()->set('translator.locales', null);

    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    expect($translator->getLocales())->toBe(['en', 'fr', 'fr_CA', 'not_a_locale', 'pt_BR', 'sublang', 'vendorlang']);
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
        'messages.register' => 'S\'inscrire',
        'messages.registered' => 'Inscrit?',
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
                $this->formatPath($this->getResourcesPath().'/views/dummy-view.blade.php'),
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
        'messages.register',
        'messages.registered',
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
        'messages.register' => 'S\'inscrire',
    ]);

});

it('replaces dot with unicode', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    $translations = $translator->getTranslations('fr_CA');

    expect($translations->toArray())->toBe([
        'dotted.This key contains a dot&#46; In the middle.And it &#46; ha&#46;s children&#46;' => 'And it has children.',
    ]);

});

it('doesn\'t break keys with dot', function () {
    $translator = new Translator(
        driver: $this->getPhpDriver(),
    );

    $translations = $translator->getTranslations('fr_CA');

    $translator->saveTranslations('fr_CA', $translations);

    expect($translator->getTranslations('fr_CA')->toArray())->toBe([
        'dotted.This key contains a dot&#46; In the middle.And it &#46; ha&#46;s children&#46;' => 'And it has children.',
    ]);

});
