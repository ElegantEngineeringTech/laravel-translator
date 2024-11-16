<?php

namespace Elegantly\Translator\Collections;

use Elegantly\Translator\Drivers\Driver;
use Illuminate\Support\Collection;

/**
 * @extends Collection<array-key, scalar|null>
 */
abstract class Translations extends Collection
{
    /**
     * @var class-string<Driver>
     */
    public string $driver;

    final public function __construct($items = [])
    {
        $this->items = parent::getArrayableItems($items);
    }

    public function notBlank(): static
    {
        return $this->filter(function ($value) {
            return ! blank($value);
        });
    }
}