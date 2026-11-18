<?php

namespace Ignescent\LoadedDice\Bandits;

/**
 * Contract for multi-armed bandit arm selection strategies.
 */
interface BanditStrategy
{
    public function pull(ExperimentalData $experimentalData): string;
}
