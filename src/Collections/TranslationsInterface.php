<?php

namespace Elegantly\Translator\Collections;

use Illuminate\Support\Collection;

interface TranslationsInterface
{
    public function set(string|int|null $key, mixed $value): static;

    public function get($key, $default = null);

    public function forget($keys);

    public function only($keys);

    public function except($keys);

    public function sanitize(): static;

    public function sortNatural(): static;

    public function toDotTranslations(): Collection;

    public function toTranslationsKeys(): Collection;

    public function toTranslationsValues(): Collection;

    public function diffTranslationsKeys(Collection $translations): Collection;

    public function toFile(): string;
}
