<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lang Paths
    |--------------------------------------------------------------------------
    |
    | This is the path were your translation files are stored. In a normal Laravel install, you should not have to change it.
    |
    */
    'lang_path' => lang_path(),


    /*
    |--------------------------------------------------------------------------
    | Auto Sort Keys
    |--------------------------------------------------------------------------
    |
    | If set to true, all keys will be sorted automatically after any file manipulation like 'edit', 'translate' or 'proofread'.
    |
    */
    'sort_keys' => false,

    /*
    |--------------------------------------------------------------------------
    | Third party services
    |--------------------------------------------------------------------------
    |
    | Define the api key of your third party services. These keys are reused both for 'translate' and 'proofread'.
    | You can override these config and define specific service options in 'translate.services.openai.key' for example.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Translation service
    |--------------------------------------------------------------------------
    |
    | This is the services that can be used to translate your strings from a local to another one.
    | You can customize their behaviour here, but you can also define your own service.
    |
    */
    'translate' => [
        /**
         * Supported: 'openai', 'deepl', 'MyOwnServiceClass::name'
         * Define your own service using the class's name: 'MyOwnServiceClass::class'
         */
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

    /*
    |--------------------------------------------------------------------------
    | Translation service
    |--------------------------------------------------------------------------
    |
    | This is the services that can be used to proofread your strings.
    | You can customize their behaviour here, but you can also define your own service.
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

    /*
    |--------------------------------------------------------------------------
    | Searchcode / Deadcode Service
    |--------------------------------------------------------------------------
    |
    | This is the services that can be used to detect dead translation strings in your codebase.
    | You can customize their behaviour here, but you can also define your own service.
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
         * By default, we exclude the default Laravel translations.
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
                 * To speed up the detection, all the results of the scan will be stored in a file.
                 * Feel free to change the path if you need.
                 */
                'cache_path' => base_path('.translator.cache'),
            ],
        ],

    ],

];
