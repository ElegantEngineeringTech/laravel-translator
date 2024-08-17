<?php

namespace Elegantly\Translator\Services\Proofread;

interface ProofreadServiceInterface
{
    public function proofreadAll(array $texts): array;

    public function proofread(string $text): ?string;
}
