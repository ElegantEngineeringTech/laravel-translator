<?php

namespace Elegantly\Translator\Collections;

use Illuminate\Support\Collection;

/**
 * @extends Collection<string|int, mixed>
 */
abstract class Translations extends Collection
{
    final public function __construct($items = [])
    {
        $this->items = parent::getArrayableItems($items);
    }
}
