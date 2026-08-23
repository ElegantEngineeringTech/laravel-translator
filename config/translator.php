<?php

declare(strict_types=1);

use Elegantly\Translator\Drivers\PhpDriver;
use Elegantly\Translator\Services\Exporter\CsvExporterService;
use Elegantly\Translator\Support\LocaleValidator;

return [

    /**
     * Possible values are: 'php', 'json' or any class-string<Driver>
     */
    'driver' => PhpDriver::class,

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
    | Locales
    |--------------------------------------------------------------------------
    |
    | If set to an array such as ['en', 'es', 'fr']:
    | -> Translator::getLocales() will return this array.
    | If set to a class implementing `\Elegantly\Translator\Contracts\ValidateLocales`:
    | -> The locales will be those found in the lang directory and filtered according to the class.
    | If set to `null`:
    | -> The locales will be those found in the lang directory.
    |
    */
    'locales' => LocaleValidator::class,

    /*
    |--------------------------------------------------------------------------
    | Third-Party Services
    |--------------------------------------------------------------------------
    |
    | Define the API keys for your third-party services. These keys are reused for both 'translate' and 'proofread'.
    | You can override this configuration and define specific service options, for example, in 'translate.services.ai.key'.
    |
    */
    'services' => [
        'ai' => [
            'provider' => 'openai',
            'model' => 'gpt-5.6-luna',
            'timeout' => 60 * 5,
            'chunk' => 50,
            'concurrency' => false,
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
         * Supported: 'ai', 'MyOwnServiceClass::name'
         * Define your own service using the class's name: 'MyOwnServiceClass::class'
         */
        'service' => 'ai',

        'prompt' => '
           # Role
            You are a professional localization copywriter and translator specializing in websites and web applications.

            # Task

            Translate the provided website copy, formatted as JSON, into the target locale: **{targetLanguage} ({targetLocale})**.

            Your goal is to produce copy that feels natural and native to users of the target locale, while preserving the original meaning, intent, and UX function.

            # Instructions

            * Preserve all JSON keys and the JSON structure **exactly**. Never translate, rename, add, remove, or reorder keys.
            * Write natural, idiomatic copy rather than translating word-for-word.
            * Adapt wording, tone, grammar, and phrasing to the conventions of **{targetLocale}**, not just the general language.
            * Preserve the original meaning, intent, tone, and UX purpose.
            * Use terminology commonly used in websites and web applications. Prefer established UI conventions for buttons, labels, navigation, forms, notifications, errors, settings, and actions.
            * Keep UI copy concise. Prefer the shortest natural translation that preserves the full meaning.
            * For languages that tend to produce longer translations, such as German, favor concise wording and shorter standard terms where possible.
            * Preserve all placeholders and variables exactly as provided, including patterns such as `{name}`, `{{name}}`, `:name`, `%s`, and similar tokens.
            * Preserve HTML tags exactly. Do not translate, modify, remove, add, or escape tags or their attributes.
            * Preserve URLs, email addresses, identifiers, and other non-translatable technical values exactly unless they are explicitly intended as user-facing copy.
            * Preserve emojis and intentional special characters.
            * Preserve interpolation, pluralization, and formatting syntax exactly.
            * Maintain valid JSON. Escape characters only when required by JSON syntax.
            * Do not translate brand names, product names, or proper nouns unless a conventional localized form exists or the context clearly requires it.
            * When a term is ambiguous, choose the translation that best fits a website or web application context.

            # Output

            Return **only valid raw JSON** with the exact same structure as the input.

            Do not include Markdown, code fences, explanations, comments, or any text outside the JSON.
        ',
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
         * Supported: 'ai', 'MyOwnServiceClass::name'
         * Define your own service using the class's name: 'MyOwnServiceClass::class'
         */
        'service' => 'ai',

        'prompt' => '
            # Role:
            You are a professional copywriter specializing in website content.

            # Task:
            Correct the grammar and syntax of the provided JSON.

            # Instructions:
            - Do not modify any JSON keys — only edit the text values.
            - Preserve the original meaning and tone of each sentence.
            - Do not escape or alter any HTML tags.
            - Do not escape or change special characters or emojis.
            - Return ONLY raw JSON (no Markdown, no code fences, no extra text).

            Output Format:
            Return a valid JSON object with the corrected text values, keeping the structure and keys unchanged.
        ',
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
                'cache_path' => storage_path('.translator.cache'),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Exporter/Importer Service
    |--------------------------------------------------------------------------
    |
    | These are the services that can be used to export and import your translations.
    | You can customize their behavior here, or you can define your own service.
    |
    */
    'exporter' => [
        'service' => CsvExporterService::class,
    ],

];
