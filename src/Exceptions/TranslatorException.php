<?php

namespace Elegantly\Translator\Exceptions;

use Exception;

class TranslatorException extends Exception
{
    public static function write(string $locale, string $namespace): self
    {
        return new self("Writing translations in {$locale}.{$namespace} failed");
    }
}
