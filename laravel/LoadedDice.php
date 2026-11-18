<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Laravel;

use Ignescent\LoadedDice\Facade;
use Illuminate\Support\Facades\Facade as LaravelBaseFacade;

/**
 * Laravel facade providing static access to the LoadedDice Facade instance.
 *
 * @see \Ignescent\LoadedDice\Facade
 */
class LoadedDice extends LaravelBaseFacade
{
    protected static function getFacadeAccessor(): string
    {
        return Facade::class;
    }
}