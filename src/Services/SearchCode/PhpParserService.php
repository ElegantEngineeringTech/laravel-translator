<?php

namespace Elegantly\Translator\Services\SearchCode;

use Illuminate\Support\Facades\Blade;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PhpParserService implements SearchCodeServiceInterface
{
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

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        $ast = $parser->parse($code);

        $nodeFinder = new NodeFinder;

        /** @var FuncCall[] $results */
        $results = $nodeFinder->find($ast, function (Node $node) {
            return $node instanceof FuncCall
                && in_array($node->name->name, ['__', 'trans', 'trans_choice']);
        });

        return collect($results)
            ->map(function (FuncCall $funcCall) {
                $args = collect($funcCall->getArgs());
                $argKey = $args->firstWhere('name.name', 'key') ?? $args->first();
                $value = $argKey->value;

                return $value instanceof String_ ? $value->value : null;
            })
            ->values()
            ->sort(SORT_NATURAL)
            ->toArray();
    }

    public function translationsByFiles(): array
    {
        return collect($this->finder())
            ->map(function (SplFileInfo $file) {
                $content = str($file->getFilename())->endsWith('.blade.php')
                    ? Blade::compileString($file->getContents())
                    : $file->getContents();

                return static::scanCode($content);
            })
            ->filter()
            ->sortKeys(SORT_NATURAL)
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

        ksort($results, SORT_NATURAL);

        return $results;
    }
}
