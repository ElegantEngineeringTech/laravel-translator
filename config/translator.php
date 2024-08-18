<?php

// config for Elegantly/Translator

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
