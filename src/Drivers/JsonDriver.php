<?php

namespace Elegantly\Translator\Drivers;

use Elegantly\Translator\Collections\JsonTranslations;
use Elegantly\Translator\Collections\Translations;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class JsonDriver extends Driver
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

    public function getFilePath(string $locale): string
    {
        return "{$locale}.json";
    }

    /**
     * @return string[]
     */
    public function getLocales(): array
    {
        return collect($this->storage->files())
            ->filter(fn (string $file) => File::extension($file) === 'json')
            ->map(fn (string $file) => File::name($file))
            ->sort(SORT_NATURAL)
            ->values()
            ->toArray();
    }

    public function getTranslations(string $locale): JsonTranslations
    {
        $path = $this->getFilePath($locale);

        if ($this->storage->exists($path)) {
            $content = $this->storage->get($path);

            return new JsonTranslations(json_decode($content, true));
        }

        return new JsonTranslations;
    }

    public function saveTranslations(string $locale, Translations $translations): Translations
    {
        $this->storage->put(
            $this->getFilePath($locale),
            $translations->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        return $translations;
    }

    public static function collect(): Translations
    {
        return new JsonTranslations;
    }
}
