<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\Exporter;

use Elegantly\Translator\Collections\Translations;

interface ExporterInterface
{
    public static function make(): self;

    /**
     * @param  array<string, Translations>  $translationsByLocale
     */
    public function export(array $translationsByLocale, string $path): string;

    /**
     * @return array<string, array<string, scalar>>
     */
    public function import(string $path): array;
}
