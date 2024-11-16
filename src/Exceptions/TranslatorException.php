<?php

namespace Elegantly\Translator\Exceptions;

use Exception;

class TranslatorException extends Exception
{
    public static function write(string $locale): self
    {
        return new self("Writing translations in {$locale} failed.");
    }
}
