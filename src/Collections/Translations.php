<?php

declare(strict_types=1);

namespace Elegantly\Translator\Collections;

use Countable;
use Elegantly\Translator\Drivers\Driver;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;

/**
 * @template TValue
 *
 * @implements Arrayable<string, TValue>
 */
abstract class Translations implements Arrayable, Countable, Jsonable
{
    /**
     * @var class-string<Driver>
     */
    public string $driver;

    /**
     * @var array<array-key, TValue>
     */
    public array $items = [];

    /**
     * @param  array<array-key, TValue>|Collection<array-key,TValue>  $items
     */
    final public function __construct(array|Collection $items = [])
    {
        $this->items = $items instanceof Collection ? $items->all() : $items;
    }

    abstract public function has(string $key): bool;

    /**
     * @return TValue
     */
    abstract public function get(string $key): mixed;

    /**
     * @return Collection<array-key, null|scalar>
     */
    abstract public function dot(): Collection;

    /**
     * @param  Collection<array-key, TValue>|array<array-key, TValue>  $items
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
     * @param  array<array-key, TValue>  $values
     */
    abstract public function merge(array $values): static;

    /**
     * @param  Translations<TValue>  $translations
     */
    abstract public function diff(Translations $translations): static;

    /**
     * @param  null|(callable(array-key, TValue):mixed)  $callback
     */
    abstract public function filter(?callable $callback = null): static;

    /**
     * @param  null|(callable(array-key, TValue):mixed)  $callback
     */
    abstract public function map(?callable $callback = null): static;

    public function notBlank(): static
    {
        return $this->filter(
            fn ($value) => ! blank($value)
        );
    }

    /**
     * @return array<array-key, TValue>
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
     * @return Collection<array-key, TValue>
     */
    public function collect(): Collection
    {
        return new Collection($this->items);
    }

    /**
     * @deprecated Use `dot` method instead
     *
     * @return Collection<array-key, null|scalar>
     */
    public function toBase(): Collection
    {
        return $this->dot();
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return array<array-key, TValue>
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
