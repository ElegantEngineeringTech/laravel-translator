# All-in-One Translations Manager for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)

![laravel-translator](https://repository-images.githubusercontent.com/816339762/eefcad09-87ad-484e-bcc4-5759303dc4b6)

Easily manage all your Laravel translation strings:

-   **Translate** strings into other languages (DeepL, OpenAI, or any custom service).
-   **Proofread** your translation strings and automatically fix grammar and syntax (OpenAI or any custom service).
-   **Find missing** translation strings across all your locales.
-   **Detect unused** translation keys (keys not used anywhere in your codebase).
-   **Sort** your translations in natural order.

## Try Laratranslate - A powerful UI to manage all your translations

[![laratranslate](https://elegantengineering.tech/assets/laratranslate/opengraph.jpg)](https://elegantengineering.tech/laratranslate/)

## Installation

You can install the package via Composer:

```bash
composer require elegantly/laravel-translator --dev
```

Next add the following lines to your `.gitignore` file:

```
.translator.cache
```

Next, publish the config file with:

```bash
php artisan vendor:publish --tag="translator-config"
```

## Configure the Driver

This package relies on the 'driver' paradigm. Out of the box it supoprts the two standard drivers: PHP and JSON.
But you can create your own driver if you need (for translations stored in database for example).

The default driver can be set in the config file:

```php
use Elegantly\Translator\Drivers\PhpDriver;

return [
        /**
     * Possible values are: 'php', 'json' or any class-string<Driver>
     */
    'driver' => PhpDriver::class,

    //...
]
```

## Sorting & Formatting

### From the CLI

```bash
php artisan translator:sort

php artisan translator:sort --driver=json
```

### From Code

```php
use Elegantly\Translator\Facades\Translator;

// Using the default driver
Translator::sortTranslations(
    locale: 'fr',
);

// Using a specific driver
Translator::driver('json')->sortTranslations(
    locale: 'fr',
);
```

## Automatic Translation

Before translating anything, you must choose and set up a translation service.

This package includes two services by default:

-   OpenAI
-   DeepL

However, you can create your own service if needed.

### Setting Up OpenAI

First, configure the OpenAI key in the config file or define the environment variables:

```php
return [
    // ...

    'services' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'request_timeout' => env('OPENAI_REQUEST_TIMEOUT'),
        ],
    ],

    // ...
]
```

### Setting Up DeepL

First, configure the DeepL key in the config file or define the environment variable:

```php
return [
    // ...

    'services' => [
        // ...
        'deepl' => [
            'key' => env('DEEPL_KEY'),
        ],
    ],

    // ...
]
```

### From the CLI

Translate missing french translations:

```bash
php artisan translator:missing en fr --translate

php artisan translator:missing en fr --translate --driver=json
```

Add a new locale and translate from english:

```bash
php artisan translator:add-locale fr en --translate

php artisan translator:add-locale fr en --translate --driver=json
```

### From Code

```php
use Elegantly\Translator\Facades\Translator;

// Translate strings defined the default driver
Translator::translateTranslations(
    source: 'fr',
    target: 'en',
    keys: ['validation.title', ...]
);

// Translate strings defined in a specific driver
Translator::driver('json')->translateTranslations(
    source: 'fr',
    target: 'en',
    keys: ['My Title', ...]
);

```

## Proofreading Translations

This package allows you to proofread (i.e., fix grammar and syntax) your translation strings.

Currently, the package includes one service:

-   OpenAI

However, you can create your own service if needed.

### Setting Up OpenAI

First, configure the OpenAI key in the config file or define the environment variables:

```php
return [
    // ...

    'services' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'request_timeout' => env('OPENAI_REQUEST_TIMEOUT'),
        ],
    ],

    // ...
]
```

### From the CLI

```bash
php artisan translator:proofread
```

### From Code

```php
use Elegantly\Translator\Facades\Translator;

// Proofread translation strings defined in the default driver
Translator::proofreadTranslations(
    locale: 'fr',
    keys: ['auth.email', ...]
);

// Proofread translation strings defined in a specific driver
Translator::driver('json')->proofreadTranslations(
    locale: 'fr',
    keys: ['My Title', ...]
);
```

## Finding Missing Translations

### From the CLI

Display the translations keys defined in 'en' locale but missing in 'fr' locale.

```bash
php artisan translator:missing en fr
```

### From Code

```php
// Compare /fr/validation.php and /en/validation.php
Translator::getMissingTranslations(
    source: 'fr',
    target: 'en',
);
```

## Finding Undefined Translations

> [!NOTE]
> Undefined translations are translations keys found in your codebase but not in the driver.

This package scans your entire codebase to find undefined translation keys. You can customize its behavior to:

-   Include or exclude specific paths.
-   Exclude translation keys.

> [!IMPORTANT]
> The dead code detector cannot detect translation keys if you use string interpolation, such as `__("countries.{$user->country})`.

### From the CLI

```bash
php artisan translator:undefined en
```

### From Code

```php
Translator::getUndefinedTranslations(
    locale: 'en',
);
```

## Finding Dead Translations

### From the CLI

```bash
php artisan translator:dead fr
```

### From Code

```php
Translator::getDeadTranslations(
    locale: 'fr',
);
```

## Configure The code scanneur

### Define Which Files/Directories Should Be Scanned

Include all paths where translation keys are likely to be used.

Both `.php` and `.blade.php` files are supported. You can customize the paths scanned in the config:

```php
return [
    // ...
    'searchcode' => [
        /**
         * Files or directories to include in the dead code scan.
         */
        'paths' => [
            app_path(), // Scan the entire /app directory
            resource_path(), // Scan the entire /resource directory
        ],
        // ...
    ]
    // ...
];
```

### Define Which Files/Directories Should Be Excluded from the Scan

To optimize or speed up the scan, you can exclude certain paths. This is particularly useful for:

-   Test files that do not rely on your translation files.
-   Entire subdirectories unrelated to your translations.

> [!TIP]
> Excluding paths will speed up the scanner.

### Ignore Translation Keys from the Search Code Detector

Sometimes, translation strings are not used in the codebase, but you don’t want to consider them as unused. For example, you might store all country names in `/countries.php`.

Sometimes, the scanner might not detect your translation strings when using string interpolation, such as `__("countries.{$user->country})`.

In these cases, you can ignore translation keys in the config:

```php
return [
    // ...
    'searchcode' => [
        // ...
        'ignored_translations' => [
            'countries', // Ignore all translation keys starting with 'countries'.
        ],
        // ...
    ]
    // ...
];
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please report any security vulnerabily to me via github or email.

## Credits

-   [Quentin Gabriele](https://github.com/QuentinGab)
-   [All Contributors](../../contributors)

## License

This package is licensed under the MIT License. Please see the [License File](LICENSE.md) for more details.
