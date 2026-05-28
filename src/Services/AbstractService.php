<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services;

use Closure;

abstract class AbstractService
{
    /**
     * @template TValue
     *
     * @param  (Closure():TValue)  $callback
     * @return TValue
     */
    protected function withTemporaryTimeout(int $limit, Closure $callback): mixed
    {
        $initial = (int) ini_get('max_execution_time');

        set_time_limit($limit);

        try {
            return $callback();
        } finally {
            set_time_limit($initial);
        }
    }
}
