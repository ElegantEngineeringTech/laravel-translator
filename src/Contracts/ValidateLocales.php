<?php

namespace Elegantly\Translator\Contracts;

interface ValidateLocales
{
    public static function make(): self;

    public function isValid(string $locale): bool;
}
