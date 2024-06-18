<?php

namespace Elegantly\Translator;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @extends Collection<string|int, array|string|int|float|null>
 */
class Translations extends Collection
{
    //

    /**
     * Set a value with dot notation
     */
    public function set(string|int $key, array|string|int|float|null $value): static
    {
        Arr::set($this->items, $key, $value);

        return $this;
    }

    public function get($key, $default = null): array|string|int|float|null
    {
        return Arr::get($this->items, $key);
    }

    public function forget($keys): static
    {
        foreach ($this->getArrayableItems($keys) as $key) {
            Arr::forget($this->items, $key);
        }

        return $this;
    }

    /**
     * Write the lines of the inner array of the language file.
     */
    public function toFile(
        ?array $items = null,
    ): string {
        $items = $items ?? $this->items;

        $output = '';

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $value = $this->toFile($value);

                $output .= "\n    '{$key}' => [{$value}\n    ],";
            } else {
                $value = str_replace('\"', '"', addslashes($value));

                $output .= "\n    '{$key}' => '{$value}',";
            }
        }

        return $output;
    }

    public function sortNatural(): static
    {
        $this->items = $this->recursiveSortNatural($this->items);

        return $this;
    }

    protected function recursiveSortNatural(array $items): array
    {
        ksort($items, SORT_NATURAL);

        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $items[$key] = $this->recursiveSortNatural($item);
            }
        }

        return $items;
    }

    public function getMissingTranslationsIn(Translations $translations): array
    {
        $dotted = $this->dot()->toBase();
        $translationsDotted = $translations->dot()->filter(fn ($item) => ! blank($item))->toBase();

        return $dotted
            ->diffKeys($translationsDotted)
            ->keys()
            ->toArray();
    }
}
