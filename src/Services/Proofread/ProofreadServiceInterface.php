<?php

namespace Elegantly\Translator\Services\Proofread;

interface ProofreadServiceInterface
{
    public function fixAll(array $texts): array;

    public function fix(string $text): ?string;
}
