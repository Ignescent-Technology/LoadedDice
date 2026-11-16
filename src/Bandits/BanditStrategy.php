<?php

namespace Ignescent\LoadedDice\Bandits;

interface BanditStrategy
{
    public function pull(ExperimentalData $experimentalData): string;
}
