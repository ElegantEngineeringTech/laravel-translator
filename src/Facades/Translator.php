<?php

namespace Elegantly\Translator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Elegantly\Translator\Translator driver(?string $name)
 * @method static void clearCache()
 *
 * @see \Elegantly\Translator\Translator
 */
class Translator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Elegantly\Translator\Translator::class;
    }
}
