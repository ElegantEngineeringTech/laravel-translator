<?php

namespace Elegantly\Translator\Services\SearchCode;

use Closure;
use Elegantly\Translator\Caches\SearchCodeCache;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
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
        ?Filesystem $cacheStorage = null,
    ) {
        if ($cacheStorage) {
            $this->cache = new SearchCodeCache($cacheStorage);
        }
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

    public static function filterTranslationsKeys(?string $key): bool
    {

        if (blank($key)) {
            return false;
        }

        preg_match(
            '/^(?<package>[a-zA-Z0-9-_]+)::(?<key>.+)$/',
            $key,
            $matches
        );

        return empty($matches);
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
                static::isFunCallTo($node->var, 'app', 'abstract', 0, 'translator')
            ) {
                return in_array($node->name->name, ['get', 'has', 'hasForLocale', 'choice']);
            }

            if ($node instanceof StaticCall && $node->class->name === Lang::class) {
                return in_array($node->name->name, ['get', 'has', 'hasForLocale', 'choice']);
            }

            if ($node instanceof FuncCall && $node->name instanceof Name) {
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

    /**
     * @param  null|(Closure(string $file, string[] $translations):void)  $progress
     */
    public function translationsByFiles(
        ?Closure $progress = null,
    ): array {
        return collect($this->finder())
            ->map(function (SplFileInfo $file, string $key) use ($progress) {

                $lastModified = $file->getMTime();
                $cachedResult = $this->cache?->get($key);

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
                            "File can't be parsed: {$file->getPath()}. Your file might contain a syntax error. You can either fix the file or add it to the ignored path.",
                            code: 422,
                            previous: $th
                        );
                    }
                    $this->cache?->put($key, $translations);
                }

                if ($progress) {
                    $progress($file, $translations);
                }

                return $translations;
            })
            ->filter()
            ->sortKeys(SORT_NATURAL)
            ->toArray();
    }

    /**
     * @param  null|(Closure(string $file, string[] $translations):void)  $progress
     */
    public function filesByTranslations(
        ?Closure $progress = null,
    ): array {
        $translations = $this->translationsByFiles($progress);

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
