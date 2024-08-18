# Laravel Translator - All in one translations file manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/elegantengineeringtech/laravel-translator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/elegantengineeringtech/laravel-translator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)

Manage all your laravel translations easily:

-   **Translate** strings to other languages (DeepL, OpenAI or any custom service)
-   **Proofread** your translations strings and automatically fix grammar and syntax (OpenAI, or any custome service)
-   **Find missing** translations strings in all your locales
-   **Find dead** translations keys (keys not used anywhere in your codebase)
-   **Sort** your tranlations in natural order

## Installation

You can install the package via composer:

```bash
composer require-dev elegantly/laravel-translator --dev
```

Unless you are using this package in production, add the following lines in `.gitignore`:

```
.translator.cache
```

Then publish the config file with:

```bash
php artisan vendor:publish --tag="translator-config"
```

This is the contents of the published config file:

```php
return [

    'lang_path' => lang_path(),

    /**
     * Auto sort translations keys after each manipulations: translate, grammar, ...
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
                'prompt' => "Translate the following json to the locale '{targetLocale}' while preserving the keys.",
            ],
        ],
    ],

    'proofread' => [
        'service' => null,
        'services' => [
            'openai' => [
                'model' => 'gpt-4o',
                'prompt' => '
                            Fix the grammar and the syntax the following json string while respecting the following rules:
                                - Never change the keys.
                                - Do not escape nor change HTML tags.
                                - Do not escape nor change special characters or emojis.
                                - Do not change the meaning or the tone of the sentences.
                            ',
            ],
        ],
    ],

    'searchcode' => [
        'service' => 'php-parser',

        /**
         * Files or directories to include in the deadcode scan
         */
        'paths' => [
            app_path(),
            resource_path(),
        ],

        /**
         * Files or directories to exclude from the deadcode scan
         */
        'excluded_paths' => [],

        /**
         * Translations keys to exclude from deadcode detection
         */
        'ignored_translations' => [
            // 'validation',
            // 'passwords',
            // 'pagination',
        ],

        'services' => [
            'php-parser' => [
                'cache_path' => base_path('.translator.cache'),
            ],
        ],

    ],
];
```

## Translate your strings automatically

Before translating anyhting, you must chose and setup a translation service. This package includes two services by default:

-   OpenAI
-   DeepL

### Setting up OpenAI

First configure the OpenAI key in the config file or define the env value:

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

### Setting up DeepL

First configure the DeepL key in the config file or define the env value:

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

### From CLI

```bash
php artisan translator:translate
```

### From code

```php
use Elegantly\Translator\Facades\Translator;

// Translate strings defined in php files
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

## Proofread your translations

This package allow you to proofread (i.e fix grammar and syntax) your translations strings.
For now the package includes one service:

-   OpenAI

But you can create you own service if you need.

### Setting up OpenAI

First configure the OpenAI key in the config file or define the env value:

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

### From CLI

```bash
php artisan translator:proofread
```

### From code

```php
use Elegantly\Translator\Facades\Translator;

// proofread translations strings defined in php files
Translator::proofreadTranslations(
    locale: 'fr',
    namespace: 'auth',
    keys: ['title', ...]
);

// proofread translations strings defined in JSON files
Translator::proofreadTranslations(
    locale: 'fr',
    namespace: null,
    keys: ['title', ...]
);
```

## Find missing translations

### From CLI

```bash
php artisan translator:missing
```

### From code

```php
// compare /fr/validation.php and /en/validation.php
Translator::getMissingTranslations(
    source: 'fr',
    target: 'en',
    namespace: 'validation'
);

// compare /fr.json and /en.json
Translator::getMissingTranslations(
    source: 'fr',
    target: 'en',
    namespace: null
);
```

## Find dead translations

> [!IMPORTANT]
> The deadcode detector can't detect the translation keys if you use string interpolation like `__("countries.{$user->country})`

### Configure code scanner

This package will scan your entire codebase to find translations keys.
You can customize its behavior to:

-   include or exlude specific paths.
-   exclude translations keys

#### Define which files/directories should be scanned

You should include all paths where translations keys are suscetible to be used.

Both `.php` and `.blade.php` files are supported. You can customize the paths scanned in the configs:

```php
return [
    // ...
    'searchcode' => [
        /**
         * Files or directories to include in the deadcode scan
         */
        'paths' => [
            app_path(), // scan the whole /app directory
            resource_path(), // scan the whole /resource directory
        ],
        // ...
    ]
    // ...
];
```

#### Define which files/directories should be excluded from the scan

If you need or to speed up the scan, you can exclude paths for the scanner, this is particularly usefull for:

-   tests files that are not rellying to your translations files
-   whole subdirectories unrelated to your translations

> [!TIP]
> Excluding paths will speedup the scanner

#### Ignore translations keys from the deadcode detector

Sometimes, translations strings are not used in the codebase but you don't want to consider them as dead.
For example, you might be storing all the countries name in `/countries.php`.

Sometimes, it's impossible for the scanner to detect your translations string because you are using string interpolation such as `__("countries.{$user->country})`.

In these cases you can ignore translations keys from the deadcode detector in the configs:

```php
return [
    // ...
    'searchcode' => [
        // ...
        'ignored_translations' => [
            'countries', // ignore all translations keys starting with 'countries'
        ],
        // ...
    ]
    // ...
];
```

### From CLI

```bash
php artisan translator:dead
```

### From code

```php
// compare /fr/validation.php and /en/validation.php
Translator::getDeadTranslations(
    locale: 'fr',
    namespace: 'validation'
);

// compare /fr.json and /en.json
Translator::getDeadTranslations(
    locale: 'fr',
    namespace: null
);
```

## Sort & format your translations files

### From CLI

```bash
php artisan translator:sort
```

### From code

```php
use Elegantly\Translator\Facades\Translator;

// sort translations from `/fr/validation.php`
Translator::sortTranslations(
    locale: 'fr',
    namespace: 'validation'
);

// sort translations from `/fr.json`
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
