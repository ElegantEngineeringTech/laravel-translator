<?php

namespace Elegantly\Translator\Collections;

use Elegantly\Translator\Drivers\PhpDriver;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;

class PhpTranslations extends Translations
{
    public string $driver = PhpDriver::class;

    /**
     * Should mimic the laravel __ method
     *
     * $items = ['foo.bar' => 'baz']
     * - $this->get('foo.bar') === 'baz'
     * - $this->get('foo') === 'baz'
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
    public static function areTranslationKeysEqual(
        int|string $translationKey,
        int|string $key
    ): bool {
        if ($translationKey === $key) {
            return true;
        }

        return str((string) $translationKey)->startsWith("{$key}.");
    }

    /**
     * @param  array<array-key, mixed>  $translations
     */
    public static function hasTranslationKey(
        array $translations,
        int|string $key
    ): bool {

        foreach ($translations as $translationKey => $translationValue) {
            if (static::areTranslationKeysEqual($translationKey, $key)) {
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
        $keys = is_array($key) ? $key : func_get_args();

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
                if (static::areTranslationKeysEqual($translationKey, $key)) {
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
                if (static::areTranslationKeysEqual($translationKey, $key)) {
                    return true;
                }
            }

            return false;
        });
    }
}
