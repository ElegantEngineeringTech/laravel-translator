<?php

namespace Elegantly\Translator\Services\Proofread;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiService implements ProofreadServiceInterface
{
    public function __construct(
        public string $model,
        public string $prompt,
    ) {
        //
    }

    public function fixAll(array $texts): array
    {
        return collect($texts)
            ->chunk(20)
            ->flatMap(function (Collection $chunk) {
                $response = OpenAI::chat()->create([
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
                $translations = json_decode($content, true);

                return $translations;
            })
            ->toArray();
    }

    public function fix(string $text): ?string
    {
        return Arr::first($this->fixAll([$text]));
    }
}
