<?php

declare(strict_types=1);

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Drivers\Driver;
use Elegantly\Translator\Translator;
use Elegantly\Translator\TranslatorServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

use function Laravel\Prompts\select;

class TranslatorCommand extends Command
{
    public function getDriverName(): ?string
    {
        /** @var string */
        return $this->option('driver');
    }

    public function getTranslator(): Translator
    {
        return \Elegantly\Translator\Facades\Translator::driver(
            $this->getDriverName()
        );
    }

    public function getDriver(): Driver
    {
        return TranslatorServiceProvider::getDriverFromConfig(
            $this->getDriverName()
        );
    }

    /**
     * @param  array<int, string>|string  $except
     * @return array<int, string>
     */
    public function getLocales(
        array|string $except = []
    ): array {
        return collect($this->getTranslator()->getLocales() ?: [config('app.locale')])
            ->filter(fn ($value) => ! in_array($value, Arr::wrap($except)))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function promptForMissingArgumentsUsing(): array
    {
        return [
            'locale' => function () {
                return select(
                    label: 'What is the locale of reference?',
                    options: $this->getLocales(),
                    default: config('app.locale'),
                    required: true,
                );
            },
            'source' => function () {
                return select(
                    label: 'What is the locale of reference?',
                    options: $this->getLocales(),
                    default: config('app.locale'),
                    required: true,
                );
            },
            'target' => function () {
                return select(
                    label: 'What is the target locale?',
                    options: $this->getLocales(
                        // @phpstan-ignore-next-line
                        except: $this->argument('source'),
                    ),
                    required: true,
                );
            },
        ];
    }
}
