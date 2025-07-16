<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Translate;

use Closure;
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
        return (int) (config('translator.translate.services.openai.request_timeout') ?? config('translator.services.openai.request_timeout') ?? 120);
    }

    /**
     * @template TValue
     *
     * @param  (Closure():TValue)  $callback
     * @return TValue
     */
    protected function withTemporaryTimeout(int $limit, Closure $callback): mixed
    {
        $initial = (int) ini_get('max_execution_time');

        set_time_limit($limit);

        try {
            return $callback();
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            set_time_limit($initial);
        }
    }

    public function translateAll(array $texts, string $targetLocale): array
    {
        return $this->withTemporaryTimeout(
            static::getTimeout(),
            function () use ($texts, $targetLocale) {
                return collect($texts)
                    ->chunk(50)
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
                        $content = str_replace('\\\/', "\/", $content);
                        $translations = json_decode($content, true);

                        return $translations;
                    })
                    ->collapse()
                    ->toArray();
            }
        );

    }
}
