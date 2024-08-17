<?php

namespace Elegantly\Translator\Collections;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @extends Collection<string|int, array|string|int|float|null>
 */
final class PhpTranslations extends Collection implements TranslationsInterface
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
