# Laravel Translator - All in one translations file manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)

Manage all your laravel translations easily:

-   Translate strings to other languages (DeepL, OpenAI or any custom service)
-   Proofread your translations strings and automatically fix grammar and syntax (OpenAI, or any custome service)
-   Find missing translations strings in all your locales
-   Find dead translations keys (keys not used anywhere in your codebase)
-   Sort your tranlations in natural order
-   Format your translations files

## Installation

You can install the package via composer:

```bash
composer require-dev elegantly/laravel-translator
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="translator-config"
```

This is the contents of the published config file:

```php
return [

    'lang_path' => lang_path(),

    'translate' => [
        'service' => 'deepl',
        'services' => [
            'deepl' => [
                'key' => env('DEEPL_KEY'),
            ],
            'openai' => [
                'model' => 'gpt-4o',
                'prompt' => "Translate the following json to the locale '{targetLocale}' while preserving the keys.",
            ],
        ],
    ],

    'grammar' => [
        'service' => 'openai',
        'services' => [
            'openai' => [
                'model' => 'gpt-4o',
                'prompt' => '
                            Fix the grammar and the syntax the following json string while preserving the keys.
                            Do not change the meaning or the tone of the sentences and never change the keys.
                            ',
            ],
        ],
    ],

];
```

## Usage

This package can be used:

-   Like a CLI tool, using commands.
-   In a programmatic way using `\Elegantly\Translator\Facades\Translator::class` facade class.

### Sort all translations in natural order

You can format and sort all your php translations files using:

```bash
php artisan translator:sort
```

```php
use Elegantly\Translator\Facades\Translator;

Translator::sortAllTranslations();
```

### Find the missing translations

You can display all the missing translations present in a given locale but not in the other ones using:

```bash
php artisan translator:missing fr
```

```php
use Elegantly\Translator\Facades\Translator;

Translator::getAllMissingTranslations('fr');
```

### Auto translate strings

This package can automatically translate your files for you.
It includes 2 services right now:

-   DeepL
-   OpenAI

You can also define your own service.

### Auto-translate using DeepL

First, you need to edit the config file to add your DeepL api key and select deepl as your service:

```php
return [
    'translate' => [
        'service' => 'deepl', // select the default service here

        'services' => [
            'deepl' => [
                'key' => env('DEEPL_KEY'), // add you api key here
            ],

        ],
    ],
]
```

To translate all the missing translations use:

```bash
php artisan translator:translate
```

To translate all translations use:

```bash
php artisan translator:translate --all
```

Ommitting the `--to` option will translate to every available languages in your project.

```php
use Elegantly\Translator\Facades\Translator;

Translator::translateTranslations(
    referenceLocale: 'fr',
    targetLocale: 'en',
    namespace: 'namespace-file',
    keys: ['title', ...]
);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Quentin Gabriele](https://github.com/40128136+QuentinGab)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
