<?php

declare(strict_types=1);

namespace Elegantly\Translator\Tests\Src\App\Ignored;

class IgnoredDummyClass
{
    public function getLabel()
    {
        return __(key: 'messages.hello');
    }
}
