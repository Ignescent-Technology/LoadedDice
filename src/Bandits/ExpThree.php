<?php

namespace Ignescent\LoadedDice\Bandits;

use Random\Randomizer;

/**
 * Exp3 (Exponential-weight algorithm for Exploration and Exploitation)
 * non-stochastic multi-armed bandit strategy with adaptive closed-form exploration.
 */
final class ExpThree implements BanditStrategy
{
    public function __construct(
        protected Randomizer $randomizer,
        public ?float $gamma = null,
    )
    {
    }

    public function pull(ExperimentalData $experimentalData): string
    {
        $experimentalData->assertHasArms();

        $arms = $experimentalData->getArms();
        $armCount = count($arms);

        if ($armCount === 1) {
            return $arms[0];
        }

        $gamma = $this->calculateGamma($experimentalData->getTotalPullCount(), $armCount);
        $probabilities = $this->calculateArmProbabilities($experimentalData, $arms, $gamma);

        return $this->sampleArm($probabilities);
    }

    private function calculateGamma(int $totalPulls, int $armCount): float
    {
        if ($this->gamma !== null) {
            return $this->gamma;
        }

        if ($totalPulls === 0) {
            return 1.0;
        }

        return min(1.0, sqrt(($armCount * log($armCount)) / ((M_E - 1.0) * $totalPulls)));
    }

    /**
     * @param list<string> $arms
     * @return array<string, float>
     */
    private function calculateArmProbabilities(ExperimentalData $experimentalData, array $arms, float $gamma): array
    {
        $armCount = count($arms);
        $weights = $this->calculateArmWeights($experimentalData, $arms, $gamma);
        $totalWeight = array_sum($weights);

        $probabilities = [];

        foreach ($arms as $arm) {
            $probabilities[$arm] = (1.0 - $gamma) * ($weights[$arm] / $totalWeight) + ($gamma / $armCount);
        }

        return $probabilities;
    }

    /**
     * @param list<string> $arms
     * @return array<string, float>
     */
    private function calculateArmWeights(ExperimentalData $experimentalData, array $arms, float $gamma): array
    {
        $armCount = count($arms);
        $totalPulls = $experimentalData->getTotalPullCount();
        $exponents = [];
        $maxExponent = 0.0;

        foreach ($arms as $arm) {
            if ($experimentalData->getArmPullCount($arm) > 0) {
                $estimatedReward = $totalPulls * $experimentalData->getArmAverage($arm);
                $exponent = ($gamma / $armCount) * $estimatedReward;
            } else {
                $exponent = 0.0;
            }

            $exponents[$arm] = $exponent;

            if ($exponent > $maxExponent) {
                $maxExponent = $exponent;
            }
        }

        $weights = [];

        foreach ($arms as $arm) {
            $weights[$arm] = exp($exponents[$arm] - $maxExponent);
        }

        return $weights;
    }

    /**
     * @param array<string, float> $probabilities
     */
    private function sampleArm(array $probabilities): string
    {
        $random = $this->randomizer->nextFloat();
        $cumulative = 0.0;

        foreach ($probabilities as $arm => $probability) {
            $cumulative += $probability;

            if ($random < $cumulative) {
                return $arm;
            }
        }

        return array_key_last($probabilities);
    }
}