<?php

declare(strict_types=1);

namespace Elegantly\Translator\Collections;

use Elegantly\Translator\Drivers\JsonDriver;

class JsonTranslations extends Translations
{
    public string $driver = JsonDriver::class;

    //
}
