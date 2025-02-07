<?php

declare(strict_types=1);

namespace Elegantly\Translator\Tests\Src\App;

class DummyClass
{
    public function getLabel()
    {
        return __(key: 'messages.dummy.class');
    }
}
