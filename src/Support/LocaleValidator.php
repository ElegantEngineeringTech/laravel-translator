<?php

declare(strict_types=1);

namespace Elegantly\Translator\Support;

use Elegantly\Translator\Contracts\ValidateLocales;
use Symfony\Component\Intl\Locales;

class LocaleValidator implements ValidateLocales
{
    public static function make(): self
    {
        return new self;
    }

    public function isValid(string $locale): bool
    {
        return Locales::exists($locale);
    }
}
