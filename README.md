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

    /*
    |--------------------------------------------------------------------------
    | Language Paths
    |--------------------------------------------------------------------------
    |
    | This is the path where your translation files are stored. In a standard Laravel installation, you should not need to change it.
    |
    */
    'lang_path' => lang_path(),

    /*
    |--------------------------------------------------------------------------
    | Auto Sort Keys
    |--------------------------------------------------------------------------
    |
    | If set to true, all keys will be sorted automatically after any file manipulation such as 'edit', 'translate', or 'proofread'.
    |
    */
    'sort_keys' => false,

    /*
    |--------------------------------------------------------------------------
    | Third-Party Services
    |--------------------------------------------------------------------------
    |
    | Define the API keys for your third-party services. These keys are reused for both 'translate' and 'proofread'.
    | You can override this configuration and define specific service options, for example, in 'translate.services.openai.key'.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Translation Service
    |--------------------------------------------------------------------------
    |
    | These are the services that can be used to translate your strings from one locale to another.
    | You can customize their behavior here, or you can define your own service.
    |
    */
    'translate' => [
        /**
         * Supported: 'openai', 'deepl', 'MyOwnServiceClass::name'
         * Define your own service using the class's name: 'MyOwnServiceClass::class'
         */
        'service' => null,
        'services' => [
            'openai' => [
                'model' => 'gpt-4o',
                'prompt' => "
                            As an experienced copywriter and translator specializing in website copy, your task is to translate the provided content from a specific website.
                            Your translations should maintain the original tone while being adapted to the target language, ensuring they are both relevant and clear.
                            The content will be provided in JSON format, and you must translate it to the locale '{targetLocale}'.
                            Ensure that all JSON keys remain preserved and unchanged.
                            ",
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proofreading Service
    |--------------------------------------------------------------------------
    |
    | These are the services that can be used to proofread your strings.
    | You can customize their behavior here, or you can define your own service.
    |
    */
    'proofread' => [
        /**
         * Supported: 'openai', 'MyOwnServiceClass::name'
         * Define your own service using the class's name: 'MyOwnServiceClass::class'
         */
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

    /*
    |--------------------------------------------------------------------------
    | Search Code / Dead Code Service
    |--------------------------------------------------------------------------
    |
    | These are the services that can be used to detect dead translation strings in your codebase.
    | You can customize their behavior here, or you can define your own service.
    |
    */
    'searchcode' => [
        /**
         * Supported: 'php-parser', 'MyOwnServiceClass::name'
         */
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
         * By default, the default Laravel translations are excluded.
         */
        'ignored_translations' => [
            'auth',
            'pagination',
            'passwords',
            'validation',
        ],

        'services' => [
            'php-parser' => [
                /**
                 * To speed up detection, all the results of the scan will be stored in a file.
                 * Feel free to change the path if needed.
                 */
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

-   [Quentin Gabriele](https://github.com/QuentinGab)
-   [All Contributors](../../contributors)

## License

This package is licensed under the MIT License. Please see the [License File](LICENSE.md) for more details.
