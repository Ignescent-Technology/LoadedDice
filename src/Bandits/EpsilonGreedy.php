<?php

namespace Ignescent\LoadedDice\Bandits;

use Random\Randomizer;

/**
 * Epsilon-greedy multi-armed bandit strategy that explores uniformly with probability epsilon
 * and exploits the arm with the highest empirical mean with probability 1 - epsilon.
 */
final class EpsilonGreedy implements BanditStrategy
{
    protected BanditStrategy $strategy;

    public function __construct(
        protected Randomizer $randomizer,
        public float $epsilon = 0.1,
    )
    {
        $this->strategy = new EpsilonFallthrough($this->randomizer, new Greedy(), $this->epsilon);
    }

    public function pull(ExperimentalData $experimentalData): string
    {
        return $this->strategy->pull($experimentalData);
    }
}
