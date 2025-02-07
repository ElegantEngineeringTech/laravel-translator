<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Translate;

use DeepL\TextResult;
use Illuminate\Support\Collection;

class DeepLService implements TranslateServiceInterface
{
    public function __construct(
        public string $key
    ) {
        //
    }

    public static function make(): self
    {
        return new self(
            key: config('translator.services.deepl.key')
        );
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

    public static function getLang(string $locale): string
    {
        return match ($locale) {
            'en' => 'EN-US',
            default => mb_strtoupper($locale),
        };
    }
}
