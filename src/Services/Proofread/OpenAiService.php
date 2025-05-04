<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Proofread;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use OpenAI;

class OpenAiService implements ProofreadServiceInterface
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
            model: config('translator.proofread.services.openai.model'),
            prompt: config('translator.proofread.services.openai.prompt'),
        );
    }

    public static function makeClient(): \OpenAI\Client
    {
        $baseUri = config('translator.proofread.services.openai.base_uri') ?? config('translator.services.openai.base_uri') ?? 'api.openai.com/v1';
        $apiKey = config('translator.proofread.services.openai.key') ?? config('translator.services.openai.key');
        $organization = config('translator.proofread.services.openai.organization') ?? config('translator.services.openai.organization');
        $project = config('translator.proofread.services.openai.project') ?? config('translator.services.openai.project');
        $timeout = config('translator.proofread.services.openai.request_timeout') ?? config('translator.services.openai.request_timeout') ?? 120;

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

    public function proofreadAll(array $texts): array
    {
        return collect($texts)
            ->chunk(20)
            ->map(function (Collection $chunk) {
                $response = $this->client->chat()->create([
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->prompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode($chunk),
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
