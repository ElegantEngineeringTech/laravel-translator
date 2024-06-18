<?php

namespace Elegantly\Translator\Services;

use Illuminate\Support\Arr;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiService implements TranslatorServiceInterface
{
    public function __construct(
        public string $model,
        public string $prompt,
    ) {
        //
    }

    public function translateAll(array $texts, string $targetLocale): array
    {
        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => str_replace('{targetLocale}', $targetLocale, $this->prompt),
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($texts),
                ],
            ],
        ]);

        $translations = $response->choices[0]->message->content;

        return array_map(function (?string $textResult) {
            return $textResult;
        }, Arr::wrap($translations));
    }

    public function translate(string $text, string $targetLocale): ?string
    {
        return Arr::first($this->translateAll([$text], $targetLocale));
    }
}
