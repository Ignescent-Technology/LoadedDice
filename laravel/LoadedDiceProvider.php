<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Laravel;

use Ignescent\LoadedDice\Hashing\Hash;
use Ignescent\LoadedDice\Hashing\Hmac;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class LoadedDiceProvider extends ServiceProvider
{
    public $bindings = [
        Hash::class => Hmac::class,
    ];

    public function register(): void
    {
        // Merges your package config into the existing application configuration
        $this->mergeConfigFrom(
            __DIR__.'/config/loaded_dice.php', 'loaded_dice'
        );

        $this->app->bind(Hmac::class, function () {
            return new Hmac(config('loaded_dice.key'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Allows users to publish the config file using: php artisan vendor:publish
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/loaded_dice.php' => config_path('loaded_dice.php'),
            ], 'loaded_dice');
        }

        Collection::macro('fauxRandom', function (int|callable|null $number = null, $preserveKeys = false): mixed {
            if (is_null($number)) return LoadedDice::randomElement($this->values()->all());
            if (is_callable($number)) $number = $number($this);

            return $this->newInstance(LoadedDice::randomSubset($number, $this->all(), $preserveKeys));
        });
    }
}