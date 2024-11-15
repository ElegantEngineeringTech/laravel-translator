<?php

namespace Elegantly\Translator\Collections;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PhpTranslations extends Translations
{
    public function set(string|int|null $key, mixed $value): static
    {
        Arr::set($this->items, $key, $value);

        return $this;
    }

    public function get($key, $default = null)
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

    public function only($keys): static
    {
        return new static(
            $this->toBase()->dot()->only($keys)->undot()
        );
    }

    public function except($keys): static
    {
        return new static(
            $this->toBase()->dot()->except($keys)->undot()
        );
    }

    /**
     * Replace empty (nested) array with null
     */
    public function sanitize(): static
    {
        return $this->map(function (mixed $value) {
            return $this->sanitizeRecursive($value);
        });
    }

    public function sanitizeRecursive(array|string|int|float|null $value)
    {
        if (is_array($value)) {
            if (empty($value)) {
                return null;
            }

            return array_map(fn ($item) => $this->sanitizeRecursive($item), $value);
        }

        return $value;
    }

    public function sortNatural(): static
    {
        $items = $this->items;

        return new static(
            $this->recursiveSortNatural($items)
        );
    }

    protected function recursiveSortNatural(array $items): array
    {
        ksort($items, SORT_NATURAL);

        return array_map(function ($item) {
            if (is_array($item)) {
                return $this->recursiveSortNatural($item);
            }

            return $item;
        }, $items);
    }

    public function toDotTranslations(): Collection
    {
        return $this->dot()->toBase();
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

    /**
     * Write the lines of the inner array of the language file.
     */
    public function toFile(): string
    {
        $content = "<?php\n\nreturn [";

        $content .= $this->recursiveToFile($this->items);

        $content .= "\n];\n";

        return $content;
    }

    public function recursiveToFile(
        array $items,
        string $prefix = '',
    ): string {

        $output = '';

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $value = $this->recursiveToFile($value, $prefix.'    ');

                if (is_string($key)) {
                    $output .= "\n{$prefix}    '{$key}' => [{$value}\n    {$prefix}],";
                } else {
                    $output .= "\n{$prefix}    [{$value}\n    {$prefix}],";
                }
            } else {
                $value = str_replace('\"', '"', addslashes($value));

                if (is_string($key)) {
                    $output .= "\n{$prefix}    '{$key}' => '{$value}',";
                } else {
                    $output .= "\n{$prefix}    '{$value}',";
                }
            }
        }

        return $output;
    }
}
