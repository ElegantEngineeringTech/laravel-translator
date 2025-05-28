<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Proofread;

interface ProofreadServiceInterface
{
    /**
     * @param  array<array-key, null|scalar>  $texts
     * @return array<array-key, null|scalar>
     */
    public function proofreadAll(array $texts): array;

    public static function make(): self;
}
