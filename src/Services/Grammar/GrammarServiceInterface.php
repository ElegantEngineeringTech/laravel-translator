<?php

namespace Elegantly\Translator\Services\Grammar;

interface GrammarServiceInterface
{
    public function fixAll(array $texts): array;

    public function fix(string $text): ?string;
}
