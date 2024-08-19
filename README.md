# Laravel Translator - All-in-One Translation File Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)

Easily manage all your Laravel translations:

-   **Translate** strings into other languages (DeepL, OpenAI, or any custom service).
-   **Proofread** your translation strings and automatically fix grammar and syntax (OpenAI or any custom service).
-   **Find missing** translation strings across all your locales.
-   **Detect unused** translation keys (keys not used anywhere in your codebase).
-   **Sort** your translations in natural order.

## Installation

You can install the package via Composer:

```bash
composer require-dev elegantly/laravel-translator --dev
```

If you’re not using this package in production, add the following lines to your `.gitignore` file:

```
.translator.cache
```

Next, publish the config file with:

```bash
php artisan vendor:publish --tag="translator-config"
```

Here’s the content of the published config file:

```php
return [

    'lang_path' => lang_path(),

    /**
     * Automatically sort translation keys after each manipulation (translate, grammar check, etc.).
     */
    'sort_keys' => false,

    'services' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'request_timeout' => env('OPENAI_REQUEST_TIMEOUT'),
        ],
        'deepl' => [
            'key' => env('DEEPL_KEY'),
        ],
    ],

    'translate' => [
        'service' => null,
        'services' => [
            'openai' => [
                'model' => 'gpt-4o',
                'prompt' => "Translate the following JSON to the locale '{targetLocale}' while preserving the keys.",
            ],
        ],
    ],

    'proofread' => [
        'service' => null,
        'services' => [
            'openai' => [
                'model' => 'gpt-4o',
                'prompt' => '
                            Fix the grammar and syntax of the following JSON string while respecting the following rules:
                                - Never change the keys.
                                - Do not escape or modify HTML tags.
                                - Do not escape or modify special characters or emojis.
                                - Do not change the meaning or tone of the sentences.
                            ',
            ],
        ],
    ],

    'searchcode' => [
        'service' => 'php-parser',

        /**
         * Files or directories to include in the dead code scan.
         */
        'paths' => [
            app_path(),
            resource_path(),
        ],

        /**
         * Files or directories to exclude from the dead code scan.
         */
        'excluded_paths' => [],

        /**
         * Translation keys to exclude from dead code detection.
         */
        'ignored_translations' => [
            'auth',
            'pagination',
            'passwords',
            'validation'
        ],

        'services' => [
            'php-parser' => [
                'cache_path' => base_path('.translator.cache'),
            ],
        ],

    ],
];
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

```bash
php artisan translator:translate
```

### From Code

```php
use Elegantly\Translator\Facades\Translator;

// Translate strings defined in PHP files
Translator::translateTranslations(
    source: 'fr',
    target: 'en',
    namespace: 'validation',
    keys: ['title', ...]
);

// Translate strings defined in JSON files
Translator::translateTranslations(
    source: 'fr',
    target: 'en',
    namespace: null,
    keys: ['title', ...]
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

// Proofread translation strings defined in PHP files
Translator::proofreadTranslations(
    locale: 'fr',
    namespace: 'auth',
    keys: ['title', ...]
);

// Proofread translation strings defined in JSON files
Translator::proofreadTranslations(
    locale: 'fr',
    namespace: null,
    keys: ['title', ...]
);
```

## Finding Missing Translations

### From the CLI

```bash
php artisan translator:missing
```

### From Code

```php
// Compare /fr/validation.php and /en/validation.php
Translator::getMissingTranslations(
    source: 'fr',
    target: 'en',
    namespace: 'validation'
);

// Compare /fr.json and /en.json
Translator::getMissingTranslations(
    source: 'fr',
    target: 'en',
    namespace: null
);
```

## Finding Unused Translations

> [!IMPORTANT]
> The dead code detector cannot detect translation keys if you use string interpolation, such as `__("countries.{$user->country})`.

### Configuring the Code Scanner

This package scans your entire codebase to find unused translation keys. You can customize its behavior to:

-   Include or exclude specific paths.
-   Exclude translation keys.

#### Define Which Files/Directories Should Be Scanned

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

#### Define Which Files/Directories Should Be Excluded from the Scan

To optimize or speed up the scan, you can exclude certain paths. This is particularly useful for:

-   Test files that do not rely on your translation files.
-   Entire subdirectories unrelated to your translations.

> [!TIP]
> Excluding paths will speed up the scanner.

#### Ignore Translation Keys from the Dead Code Detector

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

### From the CLI

```bash
php artisan translator:dead
```

### From Code

```php
// Compare /fr/validation.php and /en/validation.php
Translator::getDeadTranslations(
    locale: 'fr',
    namespace: 'validation'
);

// Compare /fr.json and /en.json
Translator::getDeadTranslations(
    locale: 'fr',
    namespace: null
);
```

## Sorting & Formatting Translation Files

### From the CLI

```bash
php artisan translator:sort
```

### From Code

```php
use Elegantly\Translator\Facades\Translator;

// Sort translations from `/fr/validation.php`
Translator::sortTranslations(
    locale: 'fr',
    namespace: 'validation'
);

// Sort translations from `/fr.json`
Translator::sortTranslations(
    locale: 'fr',
    namespace: null
);
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

-   [Quentin Gabriele](https://github.com/40128136+QuentinGab)
-   [All Contributors](../../contributors)

## License

This package is licensed under the MIT License. Please see the [License File](LICENSE.md) for more details.
