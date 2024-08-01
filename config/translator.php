<?php

// config for Elegantly/Translator

return [

    'lang_path' => lang_path(),

    /**
     * Auto sort translations keys after each manipulations: translate, grammar, ...
     */
    'sort_keys' => false,

    'translate' => [
        'service' => 'openai',
        'services' => [
            'deepl' => [
                'key' => env('DEEPL_KEY'),
            ],
            'openai' => [
                'model' => 'gpt-4o',
                'prompt' => "Translate the following json to the locale '{targetLocale}' while preserving the keys.",
            ],
        ],
    ],

    'grammar' => [
        'service' => 'openai',
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

        'paths' => [
            app_path(),
            resource_path(),
        ],

        'excluded_paths' => [],

    ],

];
