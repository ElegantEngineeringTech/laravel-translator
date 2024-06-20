<?php

// config for Elegantly/Translator

return [

    'lang_path' => lang_path(),

    'translate' => [
        'service' => 'deepl',
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
                            Fix the grammar and the syntax the following json string while preserving the keys.
                            Do not change the meaning or the tone of the sentences.
                            Your answer must always be a valid and parsable json string.
                            ',
            ],
        ],
    ],

];
