<?php

declare(strict_types=1);

namespace Ignescent\LoadedDice;

use Exception;
use Ignescent\LoadedDice\Hashing\Hash;
use Ignescent\LoadedDice\Hashing\Hmac;
use Ignescent\LoadedDice\Rendezvous\RendezvousContract;
use Ignescent\LoadedDice\Rendezvous\Simple;
use Ignescent\LoadedDice\Rendezvous\Weighted;
use Ignescent\LoadedDice\WeightResolving\MapResolver;
use Random\Randomizer;
use Stringable;

/**
 *
 *  Given the same constructor key and the same arguments, functions in this class will always return
 *  the same results.
 */
final readonly class Facade
{
    private RandomOracle $randomOracle;

    private Simple $simpleRendezvous;

    /**
     * @param Hash $hash
     */
    public function __construct(
        private Hash $hash
    )
    {
        $this->randomOracle = new RandomOracle($this->hash);
        $this->simpleRendezvous = new Simple($this->hash);
    }

    /**
     * @param string $objectIdentifier Identifier for the object to be mapped to sites.
     * @param array<string, float|int> $weightedSites A map of element to weight. Missing elements will default to 1.0.
     * @param int $siteCount How many sites to return for the object..
     * @return array<string>
     * @throws Exception
     * @link https://en.wikipedia.org/wiki/Rendezvous_hashing
     */
    public static function weightedRendezvous(
        string $objectIdentifier,
        array $weightedSites,
        int $siteCount = 1,
    ): array
    {
        $rendezvous = new Weighted(
            new Hmac($objectIdentifier, "sha256"),
            new MapResolver($weightedSites)
        );

        return $rendezvous->randomSubset($siteCount, array_keys($weightedSites));
    }

    /**
     * @see RandomOracle::boolean
     */
    public function boolean(string|Stringable $value): bool
    {
        return $this->randomOracle->boolean($value);
    }

    /**
     * @see RandomOracle::integer
     */
    public function integer(string|Stringable $value, int $max = PHP_INT_MAX): int
    {
        return $this->randomOracle->integer($value, $max);
    }

    /**
     * @see RandomOracle::float
     */
    public function float(string|Stringable $value): float
    {
        return $this->randomOracle->float($value);
    }

    /**
     * @see RendezvousContract::randomElement
     */
    public function randomElement(array $collection): string|Stringable|null
    {
        return $this->simpleRendezvous->randomElement($collection);
    }

    /**
     * @throws Exception
     * @see RendezvousContract::randomSubset
     */
    public function randomSubset(int $number, array $collection, $preserveKeys = false): array
    {
        return $this->simpleRendezvous->randomSubset($number, $collection, $preserveKeys);
    }

    public function betaGenerator(string $value, float $alpha, float $beta): Distributions\Continuous\Beta
    {
        return new Distributions\Continuous\Beta($this->seedRandomizer($value), $alpha, $beta);
    }

    public function exponentialGenerator(string $value, float $lambda): Distributions\Continuous\Exponential
    {
        return new Distributions\Continuous\Exponential($this->seedRandomizer($value), $lambda);
    }

    public function gammaGenerator(string $value, float $shape, float $scale): Distributions\Continuous\Gamma
    {
        return new Distributions\Continuous\Gamma($this->seedRandomizer($value), $shape, $scale);
    }

    public function normalGenerator(string $value, float $mean = 0.0, float $stdDev = 1.0): Distributions\Continuous\Normal
    {
        return new Distributions\Continuous\Normal($this->seedRandomizer($value), $mean, $stdDev);
    }

    public function continuousUniformGenerator(string $value, float $min = 0.0, float $max = 1.0): Distributions\Continuous\Uniform
    {
        return new Distributions\Continuous\Uniform($this->seedRandomizer($value), $min, $max);
    }

    public function binomialGenerator(string $value, int $trials, float $probability): Distributions\Discrete\Binomial
    {
        return new Distributions\Discrete\Binomial($this->seedRandomizer($value), $trials, $probability);
    }

    public function geometricGenerator(string $value, float $probability): Distributions\Discrete\Geometric
    {
        return new Distributions\Discrete\Geometric($this->seedRandomizer($value), $probability);
    }

    public function poissonGenerator(string $value, float $lambda): Distributions\Discrete\Poisson
    {
        return new Distributions\Discrete\Poisson($this->seedRandomizer($value), $lambda);
    }

    public function discreteUniformGenerator(string $value, int $min, int $max): Distributions\Discrete\Uniform
    {
        return new Distributions\Discrete\Uniform($this->seedRandomizer($value), $min, $max);
    }

    private function seedRandomizer(string $value): Randomizer
    {
        return new Randomizer($this->hash->seedEngine($value));
    }
}
