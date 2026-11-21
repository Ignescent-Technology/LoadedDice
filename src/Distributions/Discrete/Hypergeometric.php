<?php

namespace Ignescent\LoadedDice\Distributions\Discrete;

use Ignescent\LoadedDice\Utils;
use Random\Randomizer;

/**
 * Discrete Hypergeometric distribution representing the number of successes in n draws
 * without replacement from a population of size N containing K successes.
 */
final class Hypergeometric implements Distribution
{
    public function __construct(
        public int $population,
        public int $successes,
        public int $draws,
        protected Randomizer $randomizer = new Randomizer(),
    )
    {
    }

    public function random(): int
    {
        if ($this->draws <= 0 || $this->successes <= 0 || $this->population <= 0) {
            return 0;
        }

        if ($this->successes >= $this->population) {
            return $this->draws;
        }

        if ($this->draws >= $this->population) {
            return $this->successes;
        }

        $observedSuccesses = 0;
        $remainingPopulation = $this->population;
        $remainingSuccesses = $this->successes;

        for ($i = 0; $i < $this->draws; $i++) {
            if ($remainingSuccesses <= 0) {
                break;
            }

            if ($remainingSuccesses >= $remainingPopulation) {
                $observedSuccesses += ($this->draws - $i);
                break;
            }

            $probability = $remainingSuccesses / $remainingPopulation;
            if ($this->randomizer->getFloat(0.0, 1.0) < $probability) {
                $observedSuccesses++;
                $remainingSuccesses--;
            }

            $remainingPopulation--;
        }

        return $observedSuccesses;
    }

    public function cdf(int $x): float
    {
        if ($this->population < 0 || $this->successes < 0 || $this->draws < 0) {
            return 0.0;
        }

        if ($this->successes > $this->population || $this->draws > $this->population) {
            return 0.0;
        }

        $minSuccesses = max(0, $this->draws - ($this->population - $this->successes));
        $maxSuccesses = min($this->draws, $this->successes);

        if ($x < $minSuccesses) {
            return 0.0;
        }

        if ($x >= $maxSuccesses) {
            return 1.0;
        }

        $cdf = 0.0;
        for ($k = $minSuccesses; $k <= $x; $k++) {
            $cdf += $this->pmf($k);
        }

        return max(0.0, min(1.0, $cdf));
    }

    public function pmf(int $x): float
    {
        if ($this->population < 0 || $this->successes < 0 || $this->draws < 0) {
            return 0.0;
        }

        if ($this->successes > $this->population || $this->draws > $this->population) {
            return 0.0;
        }

        $minSuccesses = max(0, $this->draws - ($this->population - $this->successes));
        $maxSuccesses = min($this->draws, $this->successes);

        if ($x < $minSuccesses || $x > $maxSuccesses) {
            return 0.0;
        }

        if ($this->population === 0) {
            return $x === 0 ? 1.0 : 0.0;
        }

        $logCombKx = Utils::logGamma($this->successes + 1)
            - Utils::logGamma($x + 1)
            - Utils::logGamma($this->successes - $x + 1);

        $logCombNKnx = Utils::logGamma($this->population - $this->successes + 1)
            - Utils::logGamma($this->draws - $x + 1)
            - Utils::logGamma(($this->population - $this->successes) - ($this->draws - $x) + 1);

        $logCombNn = Utils::logGamma($this->population + 1)
            - Utils::logGamma($this->draws + 1)
            - Utils::logGamma($this->population - $this->draws + 1);

        return exp($logCombKx + $logCombNKnx - $logCombNn);
    }
}
