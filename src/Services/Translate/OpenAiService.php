<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Translate;

use InvalidArgumentException;
use OpenAI;

class OpenAiService implements TranslateServiceInterface
{
    public function __construct(
        public \OpenAI\Client $client,
        public string $model,
        public string $prompt,
    ) {
        //
    }

    public static function make(): self
    {
        return new self(
            client: static::makeClient(),
            model: config('translator.translate.services.openai.model'),
            prompt: config('translator.translate.services.openai.prompt'),
        );
    }

    public static function makeClient(): \OpenAI\Client
    {
        $baseUri = config('translator.translate.services.openai.base_uri') ?? config('translator.services.openai.base_uri') ?? 'api.openai.com/v1';
        $apiKey = config('translator.translate.services.openai.key') ?? config('translator.services.openai.key');
        $organization = config('translator.translate.services.openai.organization') ?? config('translator.services.openai.organization');
        $project = config('translator.translate.services.openai.project') ?? config('translator.services.openai.project');
        $timeout = config('translator.translate.services.openai.request_timeout') ?? config('translator.services.openai.request_timeout') ?? 120;

        if (blank($apiKey)) {
            throw new InvalidArgumentException(
                'The OpenAI API Key is missing. Please publish the [translator.php] configuration file and set the [translator.services.openai.key] value.'
            );
        }

        return OpenAI::factory()
            ->withBaseUri($baseUri)
            ->withApiKey($apiKey)
            ->withOrganization($organization)
            ->withProject($project)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => $timeout]))
            ->make();
    }

    public function translateAll(array $texts, string $targetLocale): array
    {
        return collect($texts)
            ->chunk(20)
            ->map(function ($chunk) use ($targetLocale) {
                $response = $this->client->chat()->create([
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => str_replace('{targetLocale}', $targetLocale, $this->prompt),
                        ],
                        [
                            'role' => 'user',
                            'content' => $chunk->toJson(),
                        ],
                    ],
                ]);

                $content = $response->choices[0]->message->content;
                $translations = json_decode($content, true);

                return $translations;
            })
            ->collapse()
            ->toArray();
    }
}
