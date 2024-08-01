<?php

namespace Elegantly\Translator\Exceptions;

use Exception;

class TranslatorServiceException extends Exception
{
    public static function missingTranslateService(): self
    {
        return new self('The translate service is missing. Please define a translate service in configs.');
    }

    public static function missingGrammarService(): self
    {
        return new self('The grammar service is missing. Please define a grammar service in configs.');
    }

    public static function missingSearchcodeService(): self
    {
        return new self('The searchcode service is missing. Please define a searchcode service in configs.');
    }
}
