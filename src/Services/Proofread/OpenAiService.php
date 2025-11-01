<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Proofread;

use Elegantly\Translator\Services\AbstractOpenAiService;
use Illuminate\Support\Facades\Concurrency;
use InvalidArgumentException;
use OpenAI;

class OpenAiService extends AbstractOpenAiService implements ProofreadServiceInterface
{
    public function __construct(
        public \OpenAI\Client $client,
        public string $model,
        public string $prompt,
        public bool $concurrency,
        public int $chunk,
    ) {
        //
    }

    public static function make(): self
    {
        return new self(
            client: static::makeClient(),
            model: config('translator.proofread.services.openai.model'),
            prompt: config('translator.proofread.services.openai.prompt'),
            concurrency: config('translator.proofread.services.openai.concurrency') ?? true,
            chunk: config('translator.proofread.services.openai.chunk') ?? 10,
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

    public static function getTimeout(): int
    {
        return (int) (config('translator.proofread.services.openai.request_timeout') ?? parent::getTimeout());
    }

    /**
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public function proofreadAllWithConcurrency(array $texts): array
    {
        $prompt = $this->prompt;

        $model = $this->model;

        $tasks = collect($texts)
            ->chunk($this->chunk)
            ->map(function ($chunk) use ($model, $prompt) {

                return function () use ($model, $prompt, $chunk) {
                    $response = static::makeClient()->chat()->create([
                        'model' => $model,
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $prompt,
                            ],
                            [
                                'role' => 'user',
                                'content' => json_encode($chunk),
                            ],
                        ],
                    ]);

                    $content = $response->choices[0]->message->content;
                    $content = str_replace('\\\/', "\/", $content);
                    $translations = json_decode($content, true);

                    return $translations;
                };
            })
            ->all();

        $results = $this->withTemporaryTimeout(
            static::getTimeout() * count($tasks),
            fn () => Concurrency::run($tasks),
        );

        return collect($results)->collapse()->toArray();
    }

    public function proofreadAll(array $texts): array
    {
        if ($this->concurrency) {
            return $this->proofreadAllWithConcurrency($texts);
        }

        $chunks = collect($texts)->chunk($this->chunk);

        return $this->withTemporaryTimeout(
            static::getTimeout() * count($chunks),
            fn () => $chunks->map(function ($chunk) {

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
                $content = str_replace('\\\/', "\/", $content);
                $translations = json_decode($content, true);

                return $translations;
            })->collapse()->toArray()
        );
    }
}
