<?php

namespace Elegantly\Translator\Drivers;

use Elegantly\Translator\Collections\PhpTranslations;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PhpDriver extends Driver
{
    final public function __construct(
        public Filesystem $storage,
    ) {
        //
    }

    public static function make(): static
    {
        return new static(
            storage: Storage::build([
                'driver' => 'local',
                'root' => config()->string('translator.lang_path'),
            ])
        );
    }

    /**
     * @return string[]
     */
    public function getLocales(): array
    {
        return collect($this->storage->allDirectories())
            ->sort(SORT_NATURAL)
            ->values()
            ->toArray();
    }

    public function getNamespaces(string $locale): array
    {
        return collect($this->storage->allFiles($locale))
            ->filter(fn (string $file) => File::extension($file) === 'php')
            ->map(fn (string $file) => File::name($file))
            ->sort(SORT_NATURAL)
            ->values()
            ->toArray();
    }

    public function getTranslations(string $locale): PhpTranslations
    {
        $translations = collect($this->getNamespaces($locale))
            ->mapWithKeys(function ($namespace) use ($locale) {
                return [$namespace => $this->getTranslationsInNamespace($locale, $namespace)];
            })
            ->undot();

        return new PhpTranslations($translations);
    }

    /**
     * This function uses eval and not include
     * Because using 'include' would cache/compile the code in opcache
     * Therefore it would not reflect the changes after the file is edited
     *
     * @return array<array-key, mixed>
     */
    public function getTranslationsInNamespace(string $locale, string $namespace): array
    {

        $path = $this->getFilePath($locale, $namespace);

        if ($this->storage->exists($path)) {
            $content = $this->storage->get($path);

            return eval("?> {$content}");
        }

        return [];

    }

    public function getFilePath(string $locale, string $namespace): string
    {
        return "{$locale}/{$namespace}.php";
    }
}
