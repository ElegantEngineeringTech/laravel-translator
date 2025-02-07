<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Proofread;

interface ProofreadServiceInterface
{
    /**
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    public function proofreadAll(array $texts): array;

    public static function make(): self;
}
