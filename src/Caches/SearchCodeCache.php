<?php

namespace Elegantly\Translator\Caches;

use Illuminate\Contracts\Filesystem\Filesystem;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;

class SearchCodeCache
{
    const FILENAME = 'translationsByFiles.php';

    protected ?array $value = null;

    public function __construct(public Filesystem $storage)
    {
        //
    }

    public function getValue(): ?array
    {
        if ($this->value) {
            return $this->value;
        }

        return $this->load();
    }

    public function load(): ?array
    {
        if ($this->storage->exists(static::FILENAME)) {
            $this->value = include $this->storage->path(static::FILENAME);
        }

        return $this->value;
    }

    /**
     * @return null|array{ created_at: int, translations : string[]  }
     */
    public function get(string $file): ?array
    {
        if (! $this->value) {
            $this->load();
        }

        return $this->value[$file] ?? null;
    }

    /**
     * @param  string[]  $translations
     */
    public function put(string $file, $translations): static
    {
        if (! $this->value) {
            $this->load();
        }

        $this->value[$file] = [
            'created_at' => now()->getTimestamp(),
            'translations' => $translations,
        ];

        $this->store();

        return $this;
    }

    public function flush(): static
    {
        $this->value = null;

        if ($this->storage->exists(static::FILENAME)) {
            $this->storage->delete(static::FILENAME);
        }

        return $this;
    }

    protected function store(): bool
    {
        $items = array_map(
            function (array $item, string $file) {
                return new ArrayItem(
                    key: new String_($file),
                    value: new Array_([
                        new ArrayItem(
                            key: new String_('created_at'),
                            value: new Int_($item['created_at'])
                        ),
                        new ArrayItem(
                            key: new String_('translations'),
                            value: new Array_(array_map(
                                fn (string $key) => new ArrayItem(new String_($key)),
                                $item['translations']
                            ))
                        ),
                    ])
                );
            },
            $this->value,
            array_keys($this->value)
        );

        $node = new Return_(new Array_($items));

        $prettyPrinter = new \PhpParser\PrettyPrinter\Standard;

        return $this->storage->put(
            static::FILENAME,
            $prettyPrinter->prettyPrintFile([$node])
        );
    }
}
