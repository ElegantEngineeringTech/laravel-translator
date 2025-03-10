<?php

declare(strict_types=1);

namespace Elegantly\Translator\Exceptions;

use Exception;

class TranslatorServiceException extends Exception
{
    public static function missingTranslateService(): self
    {
        return new self('The translate service is missing. Please define a translate service in configs.');
    }

    public static function missingProofreadService(): self
    {
        return new self('The proofread service is missing. Please define a proofread service in configs.');
    }

    public static function missingSearchcodeService(): self
    {
        return new self('The searchcode service is missing. Please define a searchcode service in configs.');
    }

    public static function missingExporterService(): self
    {
        return new self('The exporter service is missing. Please define an exporter service in configs.');
    }
}
