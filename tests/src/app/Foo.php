<?php

declare(strict_types=1);

namespace Elegantly\Translator\Tests\Src\App;

class Foo
{
    public function getLabel()
    {
        return __(key: 'messages.title');
    }
}
