<?php

namespace Elegantly\Translator\Services\Translate;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use OpenAI;

class OpenAiService implements TranslateServiceInterface
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

    public function translateAll(array $texts, string $targetLocale): array
    {
        return collect($texts)
            ->chunk(20)
            ->flatMap(function (Collection $chunk) use ($targetLocale) {
                $response = $this->getOpenAI()->chat()->create([
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => str_replace('{targetLocale}', $targetLocale, $this->prompt),
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
            ->toArray();
    }

    public function translate(string $text, string $targetLocale): ?string
    {
        return Arr::first($this->translateAll([$text], $targetLocale));
    }
}
