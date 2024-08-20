<?php

// config for Elegantly/Translator

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
                'prompt' => "
                            As an experienced copywriter and translator specializing in website copy, your task is to translate the provided content from a specific website. 
                            Your translations should maintain the original tone while being adapted to the target language, ensuring they are both relevant and clear. 
                            The content will be provided in JSON format, and you must translate it to the locale '{targetLocale}'. 
                            Make sure that all JSON keys remain preserved and unchanged.
                            ",
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
            'validation',
        ],

        'services' => [
            'php-parser' => [
                'cache_path' => base_path('.translator.cache'),
            ],
        ],

    ],

];
