<?php

declare(strict_types=1);

namespace Elegantly\Translator\Collections;

use Countable;
use Elegantly\Translator\Drivers\Driver;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, null|scalar|array<array-key, mixed>>
 */
abstract class Translations implements Arrayable, Countable, Jsonable
{
    /**
     * @var class-string<Driver>
     */
    public string $driver;

    /**
     * @var array<array-key, null|scalar|array<array-key, mixed>>
     */
    public array $items = [];

    /**
     * @param  array<array-key, null|scalar|array<array-key, mixed>>|Collection<array-key,null|scalar|array<array-key, mixed>>  $items
     */
    final public function __construct(array|Collection $items = [])
    {
        $this->items = $items instanceof Collection ? $items->all() : $items;
    }

    abstract public function has(string $key): bool;

    /**
     * @return null|scalar|array<array-key, mixed>
     */
    abstract public function get(string $key): mixed;

    public function getString(string $key): string
    {
        $value = $this->get($key);

        if (is_array($value)) {
            return '';
        }

        return (string) $value;
    }

    abstract public function set(string $key, null|int|float|string|bool $value): static;

    /**
     * @return Collection<array-key, null|scalar>
     */
    abstract public function dot(): Collection;

    /**
     * @param  Collection<array-key, null|scalar|array<array-key, mixed>>|array<array-key, null|scalar|array<array-key, mixed>>  $items
     */
    abstract public static function undot(Collection|array $items): static;

    /**
     * @param  array<int, array-key>  $keys
     */
    abstract public function only(array $keys): static;

    /**
     * @param  array<int, array-key>  $keys
     */
    abstract public function except(array $keys): static;

    /**
     * @param  Translations|array<array-key, null|scalar>  $values
     */
    abstract public function merge(Translations|array $values): static;

    abstract public function diff(Translations $translations): static;

    /**
     * @param  null|(callable(null|scalar|array<array-key, mixed>, array-key):mixed)  $callback
     */
    abstract public function filter(?callable $callback = null): static;

    /**
     * @param  null|(callable(null|scalar|array<array-key, mixed>, array-key):mixed)  $callback
     */
    abstract public function map(?callable $callback = null): static;

    abstract public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static;

    public function notBlank(): static
    {
        return $this->filter(
            fn ($value) => ! blank($value)
        );
    }

    /**
     * @return array<array-key, null|scalar|array<array-key, mixed>>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return array<int, array-key>
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * @return Collection<array-key, null|scalar|array<array-key, mixed>>
     */
    public function collect(): Collection
    {
        // @phpstan-ignore-next-line
        return new Collection($this->items);
    }

    /**
     * @deprecated Use `dot` method instead
     *
     * @return Collection<array-key, null|scalar>
     */
    public function toBase(): Collection
    {
        // @phpstan-ignore-next-line
        return $this->dot();
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return array<array-key, null|scalar|array<array-key, mixed>>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options) ?: '';
    }
}
