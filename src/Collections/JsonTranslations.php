<?php

declare(strict_types=1);

namespace Elegantly\Translator\Collections;

use Elegantly\Translator\Drivers\JsonDriver;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class JsonTranslations extends Translations
{
    public string $driver = JsonDriver::class;

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function get(string $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    public function dot(): Collection
    {
        // @phpstan-ignore-next-line
        return new Collection($this->items);
    }

    public static function undot(Collection|array $items): static
    {
        $items = $items instanceof Collection ? $items->all() : $items;

        return new static($items);
    }

    public function only(array $keys): static
    {
        return new static(
            array_intersect_key($this->items, array_flip((array) $keys))
        );
    }

    public function except(array $keys): static
    {
        return new static(
            array_diff_key($this->items, array_flip((array) $keys))
        );
    }

    public function merge(array $values): static
    {
        return new static(
            array_merge(
                $this->items,
                $values
            )
        );
    }

    public function diff(Translations $translations): static
    {
        return new static(
            array_diff_key(
                $this->items,
                $translations->all()
            )
        );
    }

    public function filter(?callable $callback = null): static
    {
        if ($callback) {
            return new static(array_filter(
                $this->items,
                $callback,
                ARRAY_FILTER_USE_BOTH
            ));
        }

        return new static(array_filter($this->items));

    }

    public function map(?callable $callback = null): static
    {
        return new static(
            Arr::map(
                $this->items,
                $callback
            )
        );
    }

    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static
    {
        $items = $this->items;

        $descending ? krsort($items, $options) : ksort($items, $options);

        return new static($items);
    }
}
