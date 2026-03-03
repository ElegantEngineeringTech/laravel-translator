<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Translate;

use Elegantly\Translator\Services\AbstractPrismService;
use Illuminate\Support\Facades\Concurrency;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class PrismService extends AbstractPrismService implements TranslateServiceInterface
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
            provider: config('translator.translate.services.prism.provider') ?? config('translator.services.prism.provider') ?? 'openai',
            model: config('translator.translate.services.prism.model') ?? config('translator.services.prism.model') ?? 'gpt-4.1-mini',
            prompt: config('translator.translate.services.prism.prompt') ?? config('translator.translate.services.openai.prompt'),
            concurrency: config('translator.translate.services.prism.concurrency') ?? config('translator.translate.services.openai.concurrency') ?? false,
            chunk: config('translator.translate.services.prism.chunk') ?? config('translator.translate.services.openai.chunk') ?? 10,
        );
    }

    /**
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public function translateAllWithConcurrency(array $texts, string $targetLocale): array
    {
        $provider = $this->provider;
        $model = $this->model;
        $prompt = str_replace('{targetLocale}', $targetLocale, $this->prompt);

        $tasks = collect($texts)
            ->chunk($this->chunk)
            ->map(function ($chunk) use ($provider, $model, $prompt) {
                return fn () => static::execute($chunk->all(), $provider, $model, $prompt);
            })
            ->all();

        $results = $this->withTemporaryTimeout(
            static::getTimeout(),
            fn () => Concurrency::run($tasks),
        );

        return collect($results)->collapse()->toArray();
    }

    public function translateAll(array $texts, string $targetLocale): array
    {
        if ($this->concurrency) {
            return $this->translateAllWithConcurrency($texts, $targetLocale);
        }

        $prompt = str_replace('{targetLocale}', $targetLocale, $this->prompt);

        $chunks = collect($texts)->chunk($this->chunk);

        return $this->withTemporaryTimeout(
            static::getTimeout() * count($chunks),
            fn () => $chunks->map(function ($chunk) use ($prompt) {

                return static::execute($chunk->all(), $this->provider, $this->model, $prompt);

            })->collapse()->toArray()
        );

    }

    /**
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public static function execute(
        array $texts,
        Provider|string $provider,
        string $model,
        string $prompt,
    ): array {
        $response = Prism::text()
            ->using($provider, $model)
            ->withSystemPrompt($prompt)
            ->withPrompt(json_encode($texts, JSON_THROW_ON_ERROR))
            ->asText();

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
