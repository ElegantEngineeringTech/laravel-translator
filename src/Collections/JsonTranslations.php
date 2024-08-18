<?php

namespace Elegantly\Translator\Collections;

use Illuminate\Support\Collection;

/**
 * @extends Collection<string, string|int|float|null>
 */
final class JsonTranslations extends Collection implements TranslationsInterface
{
    public function set(string|int|null $key, mixed $value): static
    {
        return $this->put($key, $value);
    }

    public function sortNatural(): static
    {
        return $this->sortKeys(SORT_NATURAL);
    }

    public function toDotTranslations(): Collection
    {
        return $this->toBase()->filter()->filter(fn ($value) => $value !== [] && $value !== null);
    }

    public function toTranslationsKeys(): Collection
    {
        return $this->toDotTranslations()->keys();
    }

    public function toTranslationsValues(): Collection
    {
        return $this->toDotTranslations()->values();
    }

    public function diffTranslationsKeys(Collection $translations): Collection
    {
        $translationsKeys = $translations
            ->dot()
            ->filter(fn ($item) => ! blank($item))
            ->keys()
            ->toBase();

        return $this
            ->toTranslationsKeys()
            ->diff($translationsKeys)
            ->values();
    }

    public function toFile(): string
    {
        return $this->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
