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

    public function sanitize(): static
    {
        return $this;
    }

    public function sortNatural(): static
    {
        return $this->sortKeys(SORT_NATURAL);
    }

    public function toDotTranslations(bool $filter = false): Collection
    {
        return $this
            ->toBase()
            ->when(
                $filter,
                fn ($c) => $c->filter(fn ($value) => ! blank($value))
            );
    }

    public function toTranslationsKeys(bool $filter = false): Collection
    {
        return $this->toDotTranslations($filter)->keys();
    }

    public function toTranslationsValues(bool $filter = false): Collection
    {
        return $this->toDotTranslations($filter)->values();
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
