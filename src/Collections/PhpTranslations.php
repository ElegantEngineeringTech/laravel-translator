<?php

declare(strict_types=1);

namespace Elegantly\Translator\Collections;

use Elegantly\Translator\Drivers\PhpDriver;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @extends Translations<null|scalar|array<array-key, mixed>>
 */
class PhpTranslations extends Translations
{
    public string $driver = PhpDriver::class;

    public function dot(): Collection
    {
        return new Collection(
            Arr::dot(
                static::prepareTranslations($this->items)
            )
        );
    }

    public static function undot(Collection|array $items): static
    {
        $items = $items instanceof Collection ? $items->all() : $items;

        return new static(
            static::unprepareTranslations(
                Arr::undot($items)
            ) ?? []
        );
    }

    public function get(string $key): mixed
    {
        return Arr::get($this->items, $key);
    }

    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    public function only(array $keys): static
    {
        $items = [];

        foreach ($keys as $key) {

            if ($this->has($key)) {
                Arr::set(
                    $items,
                    $key,
                    $this->get($key)
                );
            }
        }

        return new static($items);
    }

    /**
     * @param  array<array-key, null|scalar|array<array-key, mixed>>  $items
     * @param  string[]  $segments
     */
    protected function recursiveForget(array &$items, array $segments): void
    {
        $segment = array_shift($segments);

        if (! array_key_exists($segment, $items)) {
            return;
        }

        if (empty($segments)) {
            unset($items[$segment]);
        } elseif (is_array($items[$segment])) {
            $this->recursiveForget($items[$segment], $segments);

            if (empty($items[$segment])) {
                unset($items[$segment]);
            }
        }

    }

    public function except(array $keys): static
    {
        $items = $this->items;

        foreach ($keys as $key) {
            if (array_key_exists($key, $items)) {
                unset($items[$key]);
            } elseif (str_contains($key, '.')) {

                $this->recursiveForget(
                    $items,
                    explode('.', $key)
                );

            }
        }

        return new static($items);
    }

    /**
     * @param  array<array-key, null|scalar|array<array-key, mixed>>  $items
     * @return array<array-key, null|scalar|array<array-key, mixed>>
     */
    protected function recursiveFilter(array $items, callable $callback): array
    {
        /**
         * @var array<array-key, null|scalar|array<array-key, mixed>>
         */
        $result = [];

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                if ($subresult = $this->recursiveFilter($value, $callback)) {
                    $result[$key] = $subresult;
                }
            } elseif ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function filter(?callable $callback = null): static
    {
        return new static($this->recursiveFilter(
            $this->items,
            $callback
        ));
    }

    /**
     * @param  array<array-key, null|scalar|array<array-key, mixed>>  $items
     * @return array<array-key, null|scalar|array<array-key, mixed>>
     */
    protected function recursiveMap(array $items, callable $callback): array
    {
        /**
         * @var array<array-key, null|scalar|array<array-key, mixed>>
         */
        $result = [];

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->recursiveMap($value, $callback);
            } else {
                $result[$key] = $callback($value, $key);
            }
        }

        return $result;
    }

    public function map(?callable $callback = null): static
    {
        return new static(
            $this->recursiveMap(
                $this->items,
                $callback
            )
        );
    }

    public function merge(array $values): static
    {
        return new static(
            array_merge_recursive(
                $this->items,
                $values
            )
        );
    }

    public function diff(Translations $translations): static
    {
        return $this->except(
            $translations->dot()->keys()->all()
        );
    }

    /**
     * Dot in translations keys might break the initial array structure
     * To prevent that, we encode the dots in unicode
     */
    public static function prepareTranslations(mixed $values, bool $escape = false): mixed
    {

        if ($escape && is_string($values)) {
            return str_replace('.', '&#46;', $values);
        }

        if (! is_array($values)) {
            return $values;
        }

        if (empty($values)) {
            return null;
        }

        return Arr::mapWithKeys(
            $values,
            fn ($value, $key) => [
                static::prepareTranslations($key, true) => static::prepareTranslations($value),
            ]
        );
    }

    /**
     * Dot in translations keys might break the initial array structure
     * To prevent that, we encode the dots in unicode
     */
    public static function unprepareTranslations(mixed $values, bool $unescape = false): mixed
    {
        if ($unescape && is_string($values)) {
            return str_replace('&#46;', '.', $values);
        }

        if (! is_array($values)) {
            return $values;
        }

        if (empty($values)) {
            return null;
        }

        return Arr::mapWithKeys(
            $values,
            fn ($value, $key) => [
                static::unprepareTranslations($key, true) => static::unprepareTranslations($value),
            ]
        );
    }
}
