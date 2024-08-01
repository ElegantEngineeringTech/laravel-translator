<?php

namespace Elegantly\Translator\Services\SearchCode;

use Spatie\Regex\MatchResult;
use Spatie\Regex\Regex;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RegexService implements SearchCodeServiceInterface
{
    public static $patterns = [
        '/__\(["\'](?<key>[a-z0-9.\-_]+)["\']\)/i',
    ];

    public function __construct(
        public array $paths
    ) {
        //
    }

    public function finder(): Finder
    {
        return Finder::create()
            ->in($this->paths)
            ->followLinks()
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs(true)
            ->exclude('vendor')
            ->exclude('node_modules')
            ->name('*.php')
            ->files();
    }

    /**
     * @return string[]
     */
    public static function scanCode(string $code): array
    {
        $chunks = collect(explode("\n", $code));

        return $chunks
            ->flatMap(function (string $code) {
                return collect(static::$patterns)
                    ->flatMap(fn (string $pattern) => Regex::matchAll($pattern, $code)->results())
                    ->map(fn (MatchResult $matchResult) => $matchResult->group('key'));
            })
            ->toArray();
    }

    public function translationsByFiles(): array
    {
        return collect($this->finder())
            ->keyBy(fn (SplFileInfo $file) => $file->getRealPath())
            ->map(fn (SplFileInfo $file) => static::scanCode($file->getContents()))
            ->filter()
            ->toArray();
    }

    public function filesByTranslations(): array
    {
        $translations = $this->translationsByFiles();

        $results = [];

        foreach ($translations as $file => $keys) {
            foreach ($keys as $key) {

                $results[$key] = [
                    'count' => ($results[$key]['count'] ?? 0) + 1,
                    'files' => array_unique([
                        ...$results[$key]['files'] ?? [],
                        $file,
                    ]),
                ];
            }
        }

        return $results;
    }
}
