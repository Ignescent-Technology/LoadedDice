<?php

namespace Ignescent\LoadedDice\Rendezvous;

use Exception;
use MathieuViossat\Util\FibonacciHeap;
use Stringable;

abstract class Base implements RendezvousContract
{
    /**
     * {@inheritdoc}
     */
    public function randomElement(array $collection): string|Stringable|null
    {
        $minWeight = INF;
        $candidate = null;

        foreach ($collection as $element) {
            $weight = $this->elementPriority($element);

            if ($weight < $minWeight) {
                $minWeight = $weight;
                $candidate = $element;
            }
        }

        return $candidate;
    }

    /**
     * {@inheritdoc}
     */
    public function randomSubset(int $number, array $collection, bool $preserveKeys = false): array
    {
        $prioritized = $this->prioritizeValues($preserveKeys ? array_keys($collection) : $collection);
        $numberToSelect = min($number, sizeof($collection));
        $selected = [];

        for ($i = 0; $i < $numberToSelect; $i++) $selected[] = $prioritized->extractMin()->getData();

        if ($preserveKeys) {
            $values = [];

            foreach ($selected as $selectedKey) {
                $values[$selectedKey] = $collection[$selectedKey];
            }

            return $values;
        }

        return $selected;
    }


    /**
     * @param array<string|Stringable> $collection
     * @return FibonacciHeap A min-first priority heap of the values.
     * @throws Exception
     */
    private function prioritizeValues(array $collection): FibonacciHeap
    {
        $weighted = new FibonacciHeap();

        foreach ($collection as $element) {
            $weight = $this->elementPriority($element);
            $weighted->insert($weight, $element);
        }

        return $weighted;
    }

    /**
     * Numeric representation of the element for numeric weighting or comparisons.
     *
     * @param string|Stringable $element
     * @return float|int
     */
    protected abstract function elementPriority(string|Stringable $element): float|int;
}