<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Proofread;

use Elegantly\Translator\Services\AbstractPrismService;
use Illuminate\Support\Facades\Concurrency;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class PrismService extends AbstractPrismService implements ProofreadServiceInterface
{
    public function __construct(
        public Provider|string $provider,
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
            provider: config('translator.proofread.services.prism.provider') ?? config('translator.services.prism.provider'),
            model: config('translator.proofread.services.prism.model') ?? config('translator.services.prism.model'),
            prompt: config('translator.proofread.services.prism.prompt') ?? config('translator.proofread.services.openai.prompt'),
            concurrency: config('translator.proofread.services.prism.concurrency') ?? config('translator.proofread.services.openai.concurrency') ?? false,
            chunk: config('translator.proofread.services.prism.chunk') ?? config('translator.proofread.services.openai.chunk') ?? 10,
        );
    }

    /**
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public function proofreadAllWithConcurrency(array $texts): array
    {
        $prompt = $this->prompt;

        $provider = $this->provider;
        $model = $this->model;

        $tasks = collect($texts)
            ->chunk($this->chunk)
            ->map(function ($chunk) use ($provider, $model, $prompt) {

                return function () use ($provider, $model, $prompt, $chunk) {

                    $response = Prism::text()
                        ->using($provider, $model)
                        ->withSystemPrompt($prompt)
                        ->withPrompt($chunk->toJson())
                        ->asText();

                    $json = str_replace('\\\/', "\/", $response->text);
                    $translations = json_decode(
                        json: $json,
                        associative: true,
                        flags: JSON_THROW_ON_ERROR
                    );

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

                $response = Prism::text()
                    ->using($this->provider, $this->model)
                    ->withSystemPrompt($this->prompt)
                    ->withPrompt($chunk->toJson())
                    ->asText();

                $json = str_replace('\\\/', "\/", $response->text);
                $translations = json_decode(
                    json: $json,
                    associative: true,
                    flags: JSON_THROW_ON_ERROR
                );

                return $translations;
            })->collapse()->toArray()
        );
    }
}
