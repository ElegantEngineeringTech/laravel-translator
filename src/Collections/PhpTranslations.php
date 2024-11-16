<?php

namespace Elegantly\Translator\Collections;

use Elegantly\Translator\Drivers\PhpDriver;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PhpTranslations extends Translations
{
    public string $driver = PhpDriver::class;

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

        return $default;
    }

    public function has($key)
    {
        foreach ($this->items as $translationKey => $translationValue) {
            if ($key === $translationKey) {
                return true;
            }

            if (Str::startsWith($translationKey, $key)) {
                return true;
            }
        }

        return false;
    }
}
