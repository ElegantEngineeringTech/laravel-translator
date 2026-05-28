<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Proofread;

use Elegantly\Translator\Services\AbstractService;
use Illuminate\Support\Facades\Concurrency;
use Laravel\Ai\Enums\Lab;

use function Laravel\Ai\agent;

class AiProofreadService extends AbstractService implements ProofreadServiceInterface
{
    /**
     * @param  Lab|string[]|string|null  $provider
     */
    public function __construct(
        public string $prompt,
        public Lab|array|string|null $provider,
        public ?string $model,
        public ?int $timeout,
        public int $chunk,
        public bool $concurrency,
    ) {
        //
    }

    public static function make(): self
    {
        return new self(
            prompt: config('translator.proofread.prompt') ?? config('translator.proofread.services.ai.prompt'),
            provider: config('translator.proofread.services.ai.provider') ?? config('translator.services.ai.provider') ?? null,
            model: config('translator.proofread.services.ai.model') ?? config('translator.services.ai.model') ?? null,
            timeout: config('translator.proofread.services.ai.timeout') ?? config('translator.services.ai.timeout') ?? null,
            chunk: config('translator.proofread.services.ai.chunk') ?? config('translator.services.ai.chunk') ?? 10,
            concurrency: config('translator.proofread.services.ai.concurrency') ?? config('translator.services.ai.concurrency') ?? false,
        );
    }

    /**
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public function proofreadAllWithConcurrency(array $texts): array
    {
        $provider = $this->provider;
        $model = $this->model;
        $timeout = $this->timeout;
        $prompt = $this->prompt;

        $tasks = collect($texts)
            ->chunk($this->chunk)
            ->map(function ($chunk) use ($provider, $model, $prompt, $timeout) {
                return fn () => static::execute(
                    texts: $chunk->all(),
                    prompt: $prompt,
                    provider: $provider,
                    model: $model,
                    timeout: $timeout,
                );
            })
            ->all();

        $results = $this->withTemporaryTimeout(
            $this->timeout * 2,
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
            $this->timeout * 2 * count($chunks),
            fn () => $chunks->map(function ($chunk) {

                return static::execute(
                    texts: $chunk->all(),
                    prompt: $this->prompt,
                    provider: $this->provider,
                    model: $this->model,
                    timeout: $this->timeout,
                );

            })->collapse()->toArray()
        );

    }

    /**
     * @param  Lab|string[]|string|null  $provider
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public static function execute(
        array $texts,
        string $prompt,
        Lab|array|string|null $provider,
        ?string $model,
        ?int $timeout,
    ): array {

        $translator = agent($prompt);

        $response = $translator->prompt(
            prompt: json_encode($texts, JSON_THROW_ON_ERROR),
            provider: $provider,
            model: $model,
            timeout: $timeout,
        );

        $json = str($response->text)
            ->replaceStart('```json\n', '')
            ->replaceStart('```json', '')
            ->replaceEnd('```', '')
            ->replaceEnd('\n```', '')
            ->replace('\\\/', '\/')
            ->value();

        $translations = json_decode(
            json: $json,
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );

        return $translations;
    }
}
