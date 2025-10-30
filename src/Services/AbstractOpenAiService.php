<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services;

use Closure;

abstract class AbstractOpenAiService
{
    public static function getTimeout(): int
    {
        return (int) (config('translator.services.openai.request_timeout') ?? 120);
    }

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
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            set_time_limit($initial);
        }
    }
}
