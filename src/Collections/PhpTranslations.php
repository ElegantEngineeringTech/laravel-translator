<?php

declare(strict_types=1);

namespace Elegantly\Translator\Collections;

use Elegantly\Translator\Drivers\PhpDriver;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;

class PhpTranslations extends Translations
{
    public string $driver = PhpDriver::class;

    /**
     * Should then mimic the laravel __ method
     *
     * ! $this->items are dotted !
     *
     * $items = ['foo.bar' => 'baz']
     * - $this->get('foo.bar') === 'baz'
     * - $this->get('foo') === [ 'bar' => 'baz']
     * - $this->get('bar') === null
     */
    public function get($key, $default = null)
    {
        $values = [];

        foreach ($this->items as $translationKey => $translationValue) {
            if ($key === $translationKey) {
                return $translationValue;
            }

            if (Str::startsWith($translationKey, $key)) {
                $values[$translationKey] = $translationValue;
            }
        }

        if (! empty($values)) {
            return Arr::get(
                Arr::undot($values),
                $key
            );
        }

        // @phpstan-ignore-next-line
        return $default;
    }

    /**
     * Should mimic the laravel __ method
     *
     * - 'auth.user.email' === 'auth.user.email' // true
     * - 'auth.user.email' === 'auth.user' // true
     * - 'auth.user.email' === 'auth.user.email.label' // false
     * - 'auth.user.email' === 'auth.admin' // false
     */
    public static function isSubTranslationKey(
        int|string $translationKey,
        int|string $key
    ): bool {
        if ($translationKey === $key) {
            return true;
        }

        return Str::startsWith((string) $translationKey, "{$key}.");
    }

    /**
     * @param  array<array-key, mixed>  $translations
     */
    public static function hasTranslationKey(
        array $translations,
        int|string $key
    ): bool {

        foreach ($translations as $translationKey => $translationValue) {
            if (static::isSubTranslationKey($translationKey, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Should mimic the laravel __ method
     *
     * $items = ['foo.bar' => 'baz']
     * - $this->has('foo.bar') === true
     * - $this->has('foo') === true
     * - $this->has('bar') === false
     */
    public function has($key)
    {
        /** @var array<int, array-key> */
        $keys = Arr::wrap($key);

        foreach ($keys as $value) {
            if (! static::hasTranslationKey(
                translations: $this->items,
                key: $value
            )) {
                return false;
            }
        }

        return true;

    }

    /**
     * Should mimic the laravel __ method
     * $items = ['foo.bar' => 'baz']
     * - $this->except('foo.bar') === []
     * - $this->except('foo') === []
     * - $this->except('bar') === $items
     */
    public function except($keys)
    {

        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (! is_array($keys)) {
            $keys = func_get_args();
        }

        /** @var array<array-key, array-key> $keys */
        return $this->filter(function ($translationValue, $translationKey) use ($keys) {

            foreach ($keys as $key) {
                if (static::isSubTranslationKey($translationKey, $key)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Should mimic the laravel __ method
     * $items = ['foo.bar' => 'baz']
     * - $this->only('foo.bar') === $items
     * - $this->only('foo') === $items
     * - $this->only('bar') === []
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        /** @var array<array-key, array-key> $keys */
        return $this->filter(function ($translationValue, $translationKey) use ($keys) {

            foreach ($keys as $key) {
                if (static::isSubTranslationKey($translationKey, $key)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    public static function toDot(array $values): static
    {
        return new static(
            Arr::dot(static::prepareTranslations($values) ?? [])
        );
    }

    /**
     * @param  Translations|array<array-key, mixed>  $translations
     * @return array<array-key, mixed> $values
     */
    public static function toUndot(Translations|array $translations): array
    {
        $translations = $translations instanceof Translations ? $translations->all() : $translations;

        return static::unprepareTranslations(Arr::undot($translations)) ?? [];
    }

    /**
     * Dot in translations keys might break the initial array structure
     * To prevent that, we encode the dots in unicode
     */
    public static function prepareTranslations(mixed $values, bool $escape = false): mixed
    {
        if ($escape && is_string($values)) {
            return Str::replace('.', '&#46;', $values);
        }

        if (! is_array($values)) {
            return $values;
        }

        if (empty($values)) {
            return null;
        }

        return collect($values)
            ->mapWithKeys(fn ($value, $key) => [
                static::prepareTranslations($key, true) => static::prepareTranslations($value),
            ])
            ->all();
    }

    /**
     * Dot in translations keys might break the initial array structure
     * To prevent that, we encode the dots in unicode
     */
    public static function unprepareTranslations(mixed $values, bool $unescape = false): mixed
    {
        if ($unescape && is_string($values)) {
            return Str::replace('&#46;', '.', $values);
        }

        if (! is_array($values)) {
            return $values;
        }

        if (empty($values)) {
            return null;
        }

        return collect($values)
            ->mapWithKeys(fn ($value, $key) => [
                static::unprepareTranslations($key, true) => static::unprepareTranslations($value),
            ])
            ->all();
    }
}
