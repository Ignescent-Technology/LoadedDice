<?php

namespace Ignescent\LoadedDice\Bandits;

use Ignescent\LoadedDice\Distributions\Continuous\Beta;
use Random\Randomizer;

/**
 * Bayesian Thompson Sampling multi-armed bandit strategy assuming Beta-Bernoulli conjugate priors
 * for reward maximization in bounded [0, 1] payoff spaces.
 */
final class ThompsonSamplingBeta implements BanditStrategy
{
    public function __construct(
        protected Randomizer $randomizer,
    )
    {
    }

    public function pull(ExperimentalData $experimentalData): string
    {
        $experimentalData->assertHasArms();
        $scores = [];

        foreach ($experimentalData->getArms() as $arm) {
            $history = $experimentalData->getArmHistory($arm);
            $successes = array_sum($history);
            $failures = count($history) - $successes;

            $beta = new Beta($successes + 1, $failures + 1, $this->randomizer);

            $scores[$arm] = $beta->random();
        }

        return Bandit::bestArm($scores);
    }
}
