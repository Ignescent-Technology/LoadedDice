<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice\Laravel;

use Ignescent\LoadedDice\Facade;
use Illuminate\Support\Facades\Facade as LaravelBaseFacade;

class LoadedDice extends LaravelBaseFacade
{
    protected static function getFacadeAccessor(): string
    {
        return Facade::class;
    }
}