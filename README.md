# Laravel Translator - All in one translations file manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)

Manage all your laravel translations easily: Sort, find missing strings, translate automatically using DeepL, OpenAI or any custom service ...

## Installation

You can install the package via composer:

```bash
composer require elegantly/laravel-translator
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-translator-config"
```

This is the contents of the published config file:

```php
use Elegantly\Translator\Services\DeepLService;
use Elegantly\Translator\Services\OpenAiService;

return [

    'lang_path' => lang_path(),

    'service' => DeepLService::class,

    'services' => [
        DeepLService::class => [
            'key' => env('DEEPL_KEY'),
        ],
        OpenAiService::class => [
            'model' => 'gpt-4o',
            'prompt' => 'Translate the following json to the locale {targetLocale} while preserving the keys.',
        ],
    ],
];
```

## Usage

```bash
php artisan translator:sort
```

```bash
php artisan translator:missing fr
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
