# All-in-One Translations Manager for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-translator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-translator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ElegantEngineeringTech/laravel-translator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ElegantEngineeringTech/laravel-translator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/elegantly/laravel-translator.svg?style=flat-square)](https://packagist.org/packages/elegantly/laravel-translator)

![Laravel Translator](https://repository-images.githubusercontent.com/816339762/eefcad09-87ad-484e-bcc4-5759303dc4b6)

Easily manage all your Laravel translation strings with powerful features:

-   **Translate** strings into other languages using OpenAI, Claude, Gemini or custom services.
-   **Proofread** translations to fix grammar and syntax automatically (using OpenAI, Claude, Gemini or custom service).
-   **Find missing** translation strings across locales.
-   **Detect unused** translation keys in your codebase.
-   **Sort** translations in natural order.
-   **Import & Export** translations in a CSV file.

---

## Try Laratranslate – A Powerful UI for Managing Translations

[![Laratranslate](https://elegantengineering.tech/assets/laratranslate/opengraph.jpg)](https://elegantengineering.tech/laratranslate/)

---

# Table of Contents

1. [How does it work?](#how-does-it-work)
1. [Installation](#installation)
1. [Configuring the Driver](#configuring-the-driver)
1. [Configuring the Locales](#configuring-the-locales)
    - [Automatic Detection](#automatic-detection)
    - [Manual Setup](#manual-setup)
1. [Configuring the Code Scanner](#configuring-the-code-scanner)
    - [Requirements](#requirements)
    - [Included Paths](#included-paths)
    - [Excluded Paths](#excluded-paths)
    - [Ignored Translation Keys](#ignored-translation-keys)
1. [Sorting and Formatting](#sorting-and-formatting)
    - [CLI Commands](#cli-commands)
    - [Using Code](#using-code)
1. [Automatic Translation](#automatic-translation)
    - [Configuring OpenAI](#configuring-openai)
    - [Using Claude](#using-claude)
    - [CLI Translation](#cli-translation)
    - [Programmatic Translation](#programmatic-translation)
1. [Proofreading Translations](#proofreading-translations)
    - [CLI Proofreading](#cli-proofreading)
    - [Programmatic Proofreading](#programmatic-proofreading)
1. [Identifying Untranslated Translations](#identifying-untranslated-translations)
    - [CLI Usage](#cli-usage)
    - [Programmatic Usage](#programmatic-usage)
1. [Detecting Missing Translations](#detecting-missing-translations)
    - [CLI Usage](#cli-usage-1)
    - [Programmatic Usage](#programmatic-usage-1)
1. [Detecting Dead Translations](#detecting-dead-translations)
    - [CLI Usage](#cli-usage-2)
    - [Programmatic Usage](#programmatic-usage-2)
1. [Export to a CSV](#export-to-a-csv)
    - [CLI Usage](#cli-usage-3)
    - [Programmatic Usage](#programmatic-usage-3)
1. [Import from a CSV](#import-from-a-csv)
    - [CLI Usage](#cli-usage-4)
    - [Programmatic Usage](#programmatic-usage-4)

## How does it work?

This package will directly modify your translation files like `/lang/en/messages.php` or `/lang/fr.json` for example.

Both `PHP` and `JSON` files are supported.

Advanced features like dead translations detection will scan your entire codebase to find unused translation strings.

## Installation

Install the package via Composer:

```bash
composer require elegantly/laravel-translator --dev
```

Add the following line to your `.gitignore` file:

```
storage/.translator.cache
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="translator-config"
```

---

## Configuring the Driver

This package uses a driver-based architecture. By default, it supports two standard drivers: PHP and JSON.

-   Use the `PHP` driver if you store your translation strings in `.php` files, such as `/lang/en/message.php`.
-   Use the `JSON` driver if you store your translation strings in `.json` files, such as `/lang/fr.json`.

You can also create custom drivers for alternative storage methods, such as a database.

Set the default driver in the configuration file:

```php
use Elegantly\Translator\Drivers\PhpDriver;

return [
    /**
     * Possible values: 'php', 'json', or a class-string implementing Driver.
     */
    'driver' => PhpDriver::class,

    // ...
];
```

> [!NOTE]
> All features are supported in both the PHP and JSON drivers.

## Configuring the Locales

### Automatic Detection

By default, this package will attempt to determine the locales defined in your application by scanning your `lang` directory.

You can customize this behavior in the configuration file.

```php
use Elegantly\Translator\Support\LocaleValidator;

return [
    // ...
    'locales' => LocaleValidator::class,
    // ...
];
```

### Manual Setup

To set the locales manually, use the following configuration:

```php
return [
    // ...
    'locales' => ['en', 'fr', 'es'],
    // ...
];
```

---

## Configuring the Code Scanner

Service: `searchcode`.

Features:

-   [Detecting Missing Translations](#detecting-missing-translations)
-   [Detecting Dead Translations](#detecting-dead-translations)

Both the detection of dead and missing translations rely on scanning your code.

-   **Missing translations** are keys found in your codebase but missing in translation files.
-   **Dead translations** are keys defined in your translation files but unused in your codebase.

### Requirements

At the moment, this package can only scan the following files:

-   `.php`
-   `.blade.php`

> [!NOTE]
> If you use a React or Vue frontend, it would not be able to scan those files, making this feature irrelevant.

The default detector uses `nikic/php-parser` to scan all your `.php` files, including the Blade ones.

In order to be able to detect your keys, you will have to use one of the following Laravel function:

-   `__(...)`,
-   `trans(...)`
-   `trans_choice(...)`
-   `\Illuminate\Support\Facades\Lang::get(...)`
-   `\Illuminate\Support\Facades\Lang::has(...)`
-   `\Illuminate\Support\Facades\Lang::hasForLocale(...)`
-   `\Illuminate\Support\Facades\Lang::choice(...)`
-   `app('translator')->get(...)`
-   `app('translator')->has(...)`
-   `app('translator')->hasForLocale(...)`
-   `app('translator')->choice(...)`

Or one of the following Laravel Blade directive:

-   `@lang(...)`

Here is some example of do's and don'ts:

```php
__('messages.home.title'); // ✅ 'messages.home.title' is detected

foreach(__('messages.welcome.lines') as $line){
    // ✅ 'messages.welcome.lines' and all of its children are detected.
}

$key = 'messages.home.title';
__($key); // ❌ no key is detected
```

### Included Paths

Specify paths to scan for translation keys. By default, both `.php` and `.blade.php` files are supported.

```php
return [
    'searchcode' => [
        'paths' => [
            app_path(),
            resource_path(),
        ],
    ],
];
```

### Excluded Paths

Exclude irrelevant paths for optimized scanning, such as test files or unrelated directories.

```php
return [
    'searchcode' => [
        'excluded_paths' => [
            'tests'
        ],
    ],
];
```

### Ignored Translation Keys

Ignore specific translation keys:

```php
return [
    'searchcode' => [
        'ignored_translations' => [
            'countries', // Ignore keys starting with 'countries'.
        ],
    ],
];
```

---

## Sorting and Formatting

### CLI Commands

Sort translations with the default driver:

```bash
php artisan translator:sort
```

Specify a driver for sorting:

```bash
php artisan translator:sort --driver=json
```

### Using Code

Sort translations programmatically with the default driver:

```php
use Elegantly\Translator\Facades\Translator;

Translator::sortTranslations(locale: 'fr');
```

Specify a driver:

```php
Translator::driver('json')->sortTranslations(locale: 'fr');
```

---

## Automatic Translation

Service: `translate`.

Before translating, configure a translation service. The package supports:

-   **OpenAI**
-   Any model compatible with the OpenAI SDK

Custom translation services can also be implemented.

### Configuring OpenAI

Define your OpenAI credentials in the configuration file or via environment variables:

```php
return [
    // ...

    'services' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'request_timeout' => env('OPENAI_REQUEST_TIMEOUT'),
            'base_uri' => env('OPENAI_BASE_URI'),
            'project' => env('OPENAI_PROJECT'),
        ],
    ],

    // ...
];
```

### Using Claude

Anthropic offers an [API compatible with the OpenAI SDK](https://docs.anthropic.com/en/api/openai-sdk). To integrate Claude using this SDK, you simply need to update the `base_uri` to point to Anthropic's endpoint and use your Anthropic API key.

Here’s a sample configuration in PHP:

```php
return [
    // ...

    'services' => [
        'openai' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'base_uri' => 'https://api.anthropic.com/v1',
        ],
    ],

    // ...
];
```

> 💡 **Note:** Ensure your `ANTHROPIC_API_KEY` is set in your environment variables.

### CLI Translation

Display all keys defined in the source locale (English) but not translated in the target (French):

```bash
php artisan translator:untranslated en fr
```

Translate untranslated English strings into French:

```bash
php artisan translator:untranslated en fr --translate
```

Translate using a specific driver:

```bash
php artisan translator:untranslated en fr --translate --driver=json
```

Add a new locale (French) with their translations from a source (English):

```bash
php artisan translator:add-locale fr en --translate
```

### Programmatic Translation

Translate translations programmatically with the default driver:

```php
Translator::translateTranslations(
    source: 'en',
    target: 'fr',
    keys: ['validation.title', ...]
);
```

Specify a driver for translation:

```php
Translator::driver('json')->translateTranslations(
    source: 'en',
    target: 'fr',
    keys: ['My Title', ...]
);
```

---

## Proofreading Translations

Service: `proofread`.

Proofreading corrects the grammar and syntax of your translation strings.

Currently, OpenAI is the only built-in service, but custom services can be implemented.

To configure OpenAI, see [Configuring OpenAI](#configuring-openai).

### CLI Proofreading

Proofread all strings in the target locale (English).

```bash
php artisan translator:proofread en
```

### Programmatic Proofreading

Proofread translations with the default driver:

```php
Translator::proofreadTranslations(
    locale: 'fr',
    keys: ['auth.email', ...]
);
```

Specify a driver:

```php
Translator::driver('json')->proofreadTranslations(
    locale: 'fr',
    keys: ['My Title', ...]
);
```

---

## Identifying Untranslated Translations

Find keys defined in one locale but missing in another.

### CLI Usage

Display all keys defined in the source locale (English) but not in the target locale (French).

```bash
php artisan translator:untranslated en fr
```

### Programmatic Usage

```php
Translator::getUntranslatedTranslations(source: 'en', target: 'fr');
```

---

## Detecting Missing Translations

Service: `searchcode`.
Configuration: [Configuring the Code Scanner](#configuring-the-code-scanner)

Missing translations are keys found in your codebase but missing in translation files.

### CLI Usage

Find keys defined in your codebase but missing in your locale (English) using your default driver:

```bash
php artisan translator:missing en
```

Specify a driver:

```bash
php artisan translator:missing en --driver=json
```

Add the missing keys to your driver:

```bash
php artisan translator:missing en --sync
```

### Programmatic Usage

```php
Translator::getMissingTranslations(locale: 'en');
```

---

## Detecting Dead Translations

Service: `searchcode`.
Configuration: [Configuring the Code Scanner](#configuring-the-code-scanner)

Dead translations are keys defined in your locale (English) but unused in your codebase.

### CLI Usage

```bash
php artisan translator:dead en
```

### Programmatic Usage

```php
Translator::getDeadTranslations(locale: 'fr');
```

## Export to a CSV

Service: `exporter`

Export all your translation strings to a CSV file in the following format:

| Key                 | en    | fr        |
| ------------------- | ----- | --------- |
| messages.auth.login | Login | Connexion |

### CLI Usage

```bash
php artisan translator:export /path/to/my/file.csv
```

### Programmatic Usage

```php
$path = Translator::exportTranslations('/path/to/my/file.csv');
```

## Import from a CSV

Service: `exporter`

Import translation strings from a CSV file. Ensure your CSV follows the format below:

| Key                 | en    | fr        |
| ------------------- | ----- | --------- |
| messages.auth.login | Login | Connexion |

### CLI Usage

```bash
php artisan translator:import /path/to/my/file.csv
```

### Programmatic Usage

```php
$translations = Translator::importTranslations('/path/to/my/file.csv');
```

## Testing

Run tests using:

```bash
composer test
```

---

## Changelog

See the [CHANGELOG](CHANGELOG.md) for recent updates.

---

## Contributing

Check the [CONTRIBUTING](CONTRIBUTING.md) guide for details.

---

## Security Vulnerabilities

Report security vulnerabilities via GitHub or email.

---

## Credits

-   [Quentin Gabriele](https://github.com/QuentinGab)
-   [All Contributors](../../contributors)

---

## License

This package is licensed under the MIT License. See the [License File](LICENSE.md) for more details.
