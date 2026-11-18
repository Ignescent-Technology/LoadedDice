<?php

namespace Ignescent\LoadedDice\Bandits;

use Random\Randomizer;

/**
 * Multi-armed bandit strategy decorator that explores uniformly with probability epsilon
 * and delegates to the decorated strategy with probability 1 - epsilon.
 */
final class EpsilonFallthrough implements BanditStrategy
{
    public function __construct(
        protected Randomizer $randomizer,
        protected BanditStrategy $decorated,
        public float $epsilon = 0.1,
    )
    {
    }

    public function pull(ExperimentalData $experimentalData): string
    {
        $experimentalData->assertHasArms();

        $arms = $experimentalData->getArms();

        if ($this->randomizer->nextFloat() < $this->epsilon) {
            $randomArmIndex = $this->randomizer->pickArrayKeys($arms, 1)[0];

            return $arms[$randomArmIndex];
        }

        return $this->decorated->pull($experimentalData);
    }
}