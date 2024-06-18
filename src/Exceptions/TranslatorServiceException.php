<?php

namespace Elegantly\Translator\Exceptions;

use Exception;

class TranslatorServiceException extends Exception
{
    public static function missing(): self
    {
        return new self('Translator service missing. Please define a translator service in configs.');
    }
}
