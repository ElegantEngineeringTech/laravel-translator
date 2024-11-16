<?php

namespace Elegantly\Translator\Services\Proofread;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use OpenAI;

class OpenAiService implements ProofreadServiceInterface
{
    public function __construct(
        public string $apiKey,
        public ?string $organization,
        public int $timeout,
        public string $model,
        public string $prompt,
    ) {
        //
    }

    public function getOpenAI(): \OpenAI\Client
    {
        if (blank($this->apiKey)) {
            throw throw new InvalidArgumentException(
                'The OpenAI API Key is missing. Please publish the [translator.php] configuration file and set the [translator.translate.services.openai.key].'
            );
        }

        return OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withOrganization($this->organization)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => $this->timeout]))
            ->make();
    }

    public function proofreadAll(array $texts): array
    {
        return collect($texts)
            ->chunk(20)
            ->map(function (Collection $chunk) {
                $response = $this->getOpenAI()->chat()->create([
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
