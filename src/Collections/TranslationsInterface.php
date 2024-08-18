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

    public function sortNatural(): static;

    public function toDotTranslations(bool $filter = false): Collection;

    public function toTranslationsKeys(bool $filter = false): Collection;

    public function toTranslationsValues(bool $filter = false): Collection;

    public function diffTranslationsKeys(Collection $translations): Collection;

    public function toFile(): string;
}
