<?php

namespace Elegantly\Translator\Services;

use DeepL\TextResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DeepLService implements TranslatorServiceInterface
{
    public function __construct(
        public string $key
    ) {
        //
    }

    public function translateAll(array $texts, string $targetLocale): array
    {
        $deepl = new \DeepL\Translator($this->key);
        $targetLang = $this->getLang($targetLocale);

        return collect($texts)
            ->chunk(50)
            ->flatMap(function (Collection $chunk) use ($deepl, $targetLang) {
                $translations = $deepl->translateText(
                    texts: $chunk->toArray(),
                    sourceLang: null,
                    targetLang: $targetLang
                );

                return $chunk
                    ->keys()
                    ->combine($translations)
                    ->map(fn (TextResult $textResult) => $textResult->text)
                    ->toArray();
            })
            ->toArray();
    }

    public function translate(string $text, string $targetLocale): ?string
    {
        return Arr::first($this->translateAll([$text], $targetLocale));
    }

    public static function getLang(string $locale): string
    {
        return match ($locale) {
            'en' => 'en-US',
            default => $locale,
        };
    }
}
