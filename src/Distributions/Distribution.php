<?php

namespace Ignescent\LoadedDice\Distributions;

interface Distribution
{
    public function random(): float|int;
}
