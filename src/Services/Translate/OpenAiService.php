<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Translate;

use Elegantly\Translator\Services\AbstractOpenAiService;
use Illuminate\Support\Facades\Concurrency;
use InvalidArgumentException;
use OpenAI;

class OpenAiService extends AbstractOpenAiService implements TranslateServiceInterface
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
            model: config('translator.translate.services.openai.model'),
            prompt: config('translator.translate.services.openai.prompt'),
            concurrency: config('translator.translate.services.openai.concurrency') ?? true,
            chunk: config('translator.translate.services.openai.chunk') ?? 10,
        );
    }

    public static function makeClient(): \OpenAI\Client
    {
        $baseUri = config('translator.translate.services.openai.base_uri') ?? config('translator.services.openai.base_uri') ?? 'api.openai.com/v1';
        $apiKey = config('translator.translate.services.openai.key') ?? config('translator.services.openai.key');
        $organization = config('translator.translate.services.openai.organization') ?? config('translator.services.openai.organization');
        $project = config('translator.translate.services.openai.project') ?? config('translator.services.openai.project');
        $timeout = static::getTimeout();

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
        return (int) (config('translator.translate.services.openai.request_timeout') ?? parent::getTimeout());
    }

    /**
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public function translateAllWithConcurrency(array $texts, string $targetLocale): array
    {
        $model = $this->model;
        $prompt = $this->prompt;

        $tasks = collect($texts)
            ->chunk($this->chunk)
            ->map(function ($chunk) use ($model, $prompt, $targetLocale) {

                return function () use ($model, $prompt, $targetLocale, $chunk) {
                    $response = static::makeClient()->chat()->create([
                        'model' => $model,
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => str_replace('{targetLocale}', $targetLocale, $prompt),
                            ],
                            [
                                'role' => 'user',
                                'content' => $chunk->toJson(),
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

    public function translateAll(array $texts, string $targetLocale): array
    {
        if ($this->concurrency) {
            return $this->translateAllWithConcurrency($texts, $targetLocale);
        }

        $chunks = collect($texts)->chunk($this->chunk);

        return $this->withTemporaryTimeout(
            static::getTimeout() * count($chunks),
            fn () => $chunks->map(function ($chunk) use ($targetLocale) {

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
                $content = str_replace('\\\/', "\/", $content);
                $translations = json_decode($content, true);

                return $translations;
            })->collapse()->toArray()
        );

    }
}
