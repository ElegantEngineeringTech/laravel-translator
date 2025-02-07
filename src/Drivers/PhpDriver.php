<?php

declare(strict_types=1);

namespace Elegantly\Translator\Drivers;

use Elegantly\Translator\Collections\PhpTranslations;
use Elegantly\Translator\Collections\Translations;
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

    public function getFilePath(string $locale, string $namespace): string
    {
        return "{$locale}/{$namespace}.php";
    }

    /**
     * @return array<int, string>
     */
    public function getLocales(): array
    {
        return collect($this->storage->directories())
            ->sort(SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getNamespaces(string $locale): array
    {
        return collect($this->storage->allFiles($locale))
            ->filter(fn (string $file) => File::extension($file) === 'php')
            ->map(fn (string $file) => File::name($file))
            ->sort(SORT_NATURAL)
            ->values()
            ->all();
    }

    public function getTranslations(string $locale): PhpTranslations
    {
        $values = collect($this->getNamespaces($locale))
            ->mapWithKeys(function ($namespace) use ($locale) {
                return [$namespace => $this->getTranslationsInNamespace($locale, $namespace)];
            })
            ->all();

        return PhpTranslations::toDot($values);
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

            return eval(
                str($content)
                    ->after('<?php')
                    ->after('declare(strict_types=1);')
                    ->value()
            );
        }

        return [];

    }

    public function saveTranslations(string $locale, Translations $translations): Translations
    {
        $undot = PhpTranslations::toUndot($translations)->all();

        foreach ($undot as $namespace => $values) {

            $this->storage->put(
                $this->getFilePath($locale, $namespace),
                $this->toFile(
                    is_array($values) ? $values : []
                )
            );

        }

        return $translations;
    }

    /**
     * Write the lines of the inner array of the language file.
     *
     * @param  array<array-key, mixed>  $values
     */
    public function toFile(array $values): string
    {
        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn [";

        $content .= $this->recursiveToFile($values);

        $content .= "\n];\n";

        return $content;
    }

    /**
     * @param  array<array-key, mixed>  $items
     */
    public function recursiveToFile(
        array $items,
        string $prefix = '',
    ): string {

        $output = '';

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $value = $this->recursiveToFile($value, $prefix.'    ');

                if (is_string($key)) {
                    $key = str_replace('\"', '"', addslashes($key));

                    $output .= "\n{$prefix}    '{$key}' => [{$value}\n    {$prefix}],";
                } else {
                    $output .= "\n{$prefix}    [{$value}\n    {$prefix}],";
                }
            } else {
                $value = str_replace('\"', '"', addslashes($value));

                if (is_string($key)) {
                    $key = str_replace('\"', '"', addslashes($key));
                    $output .= "\n{$prefix}    '{$key}' => '{$value}',";
                } else {
                    $output .= "\n{$prefix}    '{$value}',";
                }
            }
        }

        return $output;
    }

    public static function collect(): Translations
    {
        return new PhpTranslations;
    }
}
