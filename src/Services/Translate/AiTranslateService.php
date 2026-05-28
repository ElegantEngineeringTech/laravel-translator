<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Translate;

use Elegantly\Translator\Services\AbstractService;
use Illuminate\Support\Facades\Concurrency;
use Laravel\Ai\Enums\Lab;
use Symfony\Component\Intl\Languages;

use function Laravel\Ai\agent;

class AiTranslateService extends AbstractService implements TranslateServiceInterface
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
            prompt: config('translator.translate.prompt') ?? config('translator.translate.services.ai.prompt'),
            provider: config('translator.translate.services.ai.provider') ?? config('translator.services.ai.provider') ?? null,
            model: config('translator.translate.services.ai.model') ?? config('translator.services.ai.model') ?? null,
            timeout: config('translator.translate.services.ai.timeout') ?? config('translator.services.ai.timeout') ?? null,
            chunk: config('translator.translate.services.ai.chunk') ?? config('translator.services.ai.chunk') ?? 10,
            concurrency: config('translator.translate.services.ai.concurrency') ?? config('translator.services.ai.concurrency') ?? false,
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
        $timeout = $this->timeout;

        $prompt = str($this->prompt)
            ->replace('{targetLocale}', $targetLocale)
            ->replace('{targetLanguage}', Languages::getName($targetLocale, 'en'))
            ->value();

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

    public function translateAll(array $texts, string $targetLocale): array
    {
        if ($this->concurrency) {
            return $this->translateAllWithConcurrency($texts, $targetLocale);
        }

        $prompt = str($this->prompt)
            ->replace('{targetLocale}', $targetLocale)
            ->replace('{targetLanguage}', Languages::getName($targetLocale, 'en'))
            ->value();

        $chunks = collect($texts)->chunk($this->chunk);

        return $this->withTemporaryTimeout(
            $this->timeout * 2 * count($chunks),
            fn () => $chunks->map(function ($chunk) use ($prompt) {

                return static::execute(
                    texts: $chunk->all(),
                    prompt: $prompt,
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
