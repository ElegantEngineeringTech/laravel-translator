<?php

namespace Elegantly\Translator\Services\Translate;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiService implements TranslateServiceInterface
{
    public function __construct(
        public string $model,
        public string $prompt,
    ) {
        //
    }

    public function translateAll(array $texts, string $targetLocale): array
    {
        return collect($texts)
            ->chunk(20)
            ->flatMap(function (Collection $chunk) use ($targetLocale) {
                $response = OpenAI::chat()->create([
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
