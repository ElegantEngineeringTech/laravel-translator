<?php

namespace Elegantly\Translator\Commands;

use Elegantly\Translator\Drivers\Driver;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laravel\Prompts\Table;

use function Laravel\Prompts\multiselect;

class LocalesCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'translator:locales {driver* : The drivers to use}';

    public $description = 'Retrieve the available locales.';

    public function handle(): int
    {
        /** @var class-string<Driver>[] */
        $drivers = (array) $this->argument('driver');

        $locales = array_map(fn ($class) => [
            $class,
            implode(', ', $class::make()->getLocales()),
        ], $drivers);

        $table = new Table(
            headers: ['Driver', 'Locales'],
            rows: $locales
        );

        $table->display();

        return self::SUCCESS;
    }

    public function promptForMissingArgumentsUsing()
    {
        return [
            'driver' => function () {
                return multiselect(
                    label: 'What driver would you like to use?',
                    options: config()->array('translator.drivers'),
                    default: config()->array('translator.drivers'),
                );
            },

        ];
    }
}
