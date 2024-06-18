<?php

// config for Elegantly/Translator

use Elegantly\Translator\Services\DeepLService;
use Elegantly\Translator\Services\OpenAiService;

return [

    'lang_path' => lang_path(),

    'service' => DeepLService::class,

    'services' => [
        DeepLService::class => [
            'key' => env('DEEPL_KEY'),
        ],
        OpenAiService::class => [
            'model' => 'gpt-4o',
            'prompt' => 'Translate the following json to the locale {targetLocale} while preserving the keys.',
        ],
    ],
];
