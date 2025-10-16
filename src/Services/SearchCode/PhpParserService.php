<?php

declare(strict_types=1);

namespace Elegantly\Translator\Services\SearchCode;

use Closure;
use Elegantly\Translator\Caches\SearchCodeCache;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PhpParserService implements SearchCodeServiceInterface
{
    public ?SearchCodeCache $cache = null;

    /**
     * @param  array<int, string>  $paths
     * @param  array<int, string>  $excludedPaths
     */
    public function __construct(
        public array $paths,
        public array $excludedPaths = [],
        null|string|Filesystem $cachePath = null,
    ) {
        if ($cachePath) {
            $this->cache = new SearchCodeCache(
                storage: is_string($cachePath) ? Storage::build(['driver' => 'local', 'root' => $cachePath]) : $cachePath
            );
        }
    }

    public static function make(): self
    {
        return new self(
            paths: config('translator.searchcode.paths'),
            excludedPaths: config('translator.searchcode.excluded_paths', []),
            cachePath: config('translator.searchcode.services.php-parser.cache_path')
        );
    }

    public function getCache(): ?SearchCodeCache
    {
        return $this->cache;
    }

    public function finder(): Finder
    {
        return Finder::create()
            ->in($this->paths)
            ->notPath($this->excludedPaths)
            ->exclude('vendor')
            ->exclude('node_modules')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->ignoreVCSIgnored(true)
            ->ignoreUnreadableDirs(true)
            ->name('*.php')
            ->followLinks()
            ->files();
    }

    public static function isTranslationKeyFromPackage(string $key): bool
    {
        preg_match(
            '/^(?<package>[a-zA-Z0-9-_]+)::(?<key>.+)$/',
            $key,
            $matches
        );

        return empty($matches);
    }

    public static function filterTranslationsKeys(?string $key): bool
    {

        if (blank($key)) {
            return false;
        }

        return static::isTranslationKeyFromPackage($key);
    }

    public static function isFunCallTo(
        FuncCall $node,
        string $function,
        string $argName,
        int $argPosition,
        string $argValue
    ): bool {
        if ($node->name instanceof Name && $node->name->name !== $function) {
            return false;
        }

        $args = collect($node->getArgs());

        if ($args->isEmpty()) {
            return false;
        }

        $arg = $args->firstWhere('name.name', $argName) ?? $args->get($argPosition);

        if ($arg->value instanceof String_) {
            return $arg->value->value === $argValue;
        }

        return false;
    }

    /**
     * @return string[] All translations keys used in the code
     */
    public static function scanCode(string $code): array
    {

        $parser = (new ParserFactory)->createForHostVersion();

        $ast = $parser->parse($code);

        $nodeFinder = new NodeFinder;

        /** @var FuncCall[] $results */
        $results = $nodeFinder->find($ast, function (Node $node) {

            if (
                $node instanceof MethodCall &&
                $node->var instanceof FuncCall &&
                static::isFunCallTo($node->var, 'app', 'abstract', 0, 'translator') &&
                $node->name instanceof Identifier
            ) {
                return in_array($node->name->name, ['get', 'has', 'hasForLocale', 'choice']);
            }

            if (
                $node instanceof StaticCall &&
                $node->class instanceof Name &&
                $node->class->name === Lang::class &&
                $node->name instanceof Identifier
            ) {
                return in_array($node->name->name, ['get', 'has', 'hasForLocale', 'choice']);
            }

            if (
                $node instanceof FuncCall &&
                $node->name instanceof Name
            ) {
                return in_array($node->name->name, ['__', 'trans', 'trans_choice']);
            }

            return false;
        });

        return collect($results)
            ->map(function (FuncCall|StaticCall|MethodCall $node) {
                $args = collect($node->getArgs());
                $argKey = $args->firstWhere('name.name', 'key') ?? $args->first();

                $value = $argKey->value;

                $translationKey = $value instanceof String_ ? $value->value : null;

                return $translationKey;
            })
            ->filter(fn ($value) => static::filterTranslationsKeys($value))
            ->sort(SORT_NATURAL)
            ->values()
            ->toArray();
    }

    public function translationsByFiles(
        ?Closure $progress = null,
        ?Closure $start = null,
        ?Closure $end = null,
    ): array {
        $finder = $this->finder();

        $total = $finder->count();

        if ($start) {
            $start($total);
        }

        $translations = collect($finder)
            ->map(function (SplFileInfo $file, string $path) use ($progress) {
                if ($progress) {
                    $progress($path);
                }

                $lastModified = $file->getMTime();
                $cachedResult = $this->cache?->get($path);

                if (
                    $lastModified && $cachedResult &&
                    $lastModified < $cachedResult['created_at']
                ) {
                    $translations = $cachedResult['translations'];
                } else {
                    $content = str($file->getFilename())->endsWith('.blade.php')
                        ? Blade::compileString($file->getContents())
                        : $file->getContents();

                    try {
                        $translations = static::scanCode($content);
                    } catch (\Throwable $th) {
                        throw new Exception(
                            "File can't be parsed: {$file->getRealPath()}. Your file might contain a syntax error. You can either fix the file or add it to the ignored path.",
                            code: 422,
                            previous: $th
                        );
                    }
                    $this->cache?->put($path, $translations);
                }

                return $translations;
            })
            ->filter()
            ->sortKeys(SORT_NATURAL)
            ->toArray();

        if ($end) {
            $end();
        }

        return $translations;
    }

    public function filesByTranslations(
        ?Closure $progress = null,
        ?Closure $start = null,
        ?Closure $end = null,
    ): array {
        $translations = $this->translationsByFiles(
            progress: $progress,
            start: $start,
            end: $end
        );

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

        ksort($results, SORT_NATURAL);

        return $results;
    }
}
