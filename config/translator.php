<?php

// config for Elegantly/Translator

use Elegantly\Translator\Services\DeepLService;

return [

    'lang_path' => lang_path(),

    'service' => DeepLService::class,

    'services' => [
        'deepl' => [
            'key' => env('DEEPL_KEY'),
        ],
        'openai' => [
            'model' => 'gpt-4o',
            'prompt' => "Translate the following json to the locale '{targetLocale}' while preserving the keys.",
        ],
    ],
];
