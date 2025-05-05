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
    | You can override this configuration and define specific service options, for example, in 'translate.services.openai.key'.
    |
    */
    'services' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'request_timeout' => env('OPENAI_REQUEST_TIMEOUT'),
            'base_uri' => env('OPENAI_BASE_URL'),
            'project' => env('OPENAI_PROJECT'),
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
         * Supported: 'openai', 'MyOwnServiceClass::name'
         * Define your own service using the class's name: 'MyOwnServiceClass::class'
         */
        'service' => null,
        'services' => [
            'openai' => [
                'model' => 'gpt-4o-mini',
                'prompt' => '
                           You are an experienced copywriter and translator with a focus on website content.
                           Your task is to translate the provided website copy, formatted in JSON, into the target locale: {targetLocale}.
                           Preserve all JSON keys exactly as they are. 
                           Adapt the tone, clarity, and relevance of the content to suit the target language while staying true to the original intent.
                        ',
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
                'model' => 'gpt-4o-mini',
                'prompt' => '
                            Correct the grammar and syntax of the following JSON string while strictly adhering to these rules:
                            - Do not modify the JSON keys.
                            - Do not escape or alter HTML tags.
                            - Do not escape or change special characters or emojis.
                            - Preserve the original meaning and tone of each sentence.
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
