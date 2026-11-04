<?php

namespace Ignescent\LoadedDice;

/**
 * @private Not part of the public API, liable to change without notice.
 */
final class Utils
{
    /**
     * Converts a hash output to the bounded [0, 1) range, useful for probability calculations.
     *
     * @param string $hash A hex string from a hash function.
     * @return float  [0, 1)
     */
    public static function hashToFloat(string $hash): float
    {
        $hashBytes = (int) floor(strlen($hash) / 2);
        $workingBytes = min($hashBytes, PHP_INT_SIZE);

        if (!$workingBytes) return 0.0;

        /* hexdec treats the input as unsigned, so the output would be an unsigned int,
         * but PHP ints are always signed. That means that PHP_INT_SIZE bytes can be 2 * PHP_INT_MAX.
         */
        $value = hexdec(substr($hash, 0, $workingBytes * 2));

        // And so this is actually a float twice PHP_INT_MAX for longer hash inputs.
        $maxValue = (2 ** ($workingBytes * 8)) - 1; // Unsigned at N bytes, but that can become a float.

        return $value / $maxValue;
    }

    private const float LANCZOS_G = 607.0 / 128.0;

    private const array LANCZOS_COEFFICIENTS = [
        0.99999999999999709182,
        57.156235665862923517,
        -59.597960355475491248,
        14.136097974741747174,
        -0.49191381609762019978,
        0.33994649984811888699e-4,
        0.46523628927048575665e-4,
        -0.98374475304879564677e-4,
        0.15808870322437947113e-3,
        -0.21026444172410488319e-3,
        0.21743961811521261655e-3,
        -0.16431810653676389022e-3,
        0.84418223983852743293e-4,
        -0.26190838401581408670e-4,
        0.36899182659531622704e-5,
    ];

    /**
     * Computes the gamma function for a given input.
     *
     * The gamma function is defined for all real and complex numbers
     * except for non-positive integers. It generalizes the factorial
     * function to real numbers.
     *
     * @param float $x The input value for which the gamma function is calculated.
     *                 If $x is a non-positive integer, the function will return INF.
     * @return float The computed value of the gamma function for the input $x.
     */
    public static function gamma(float $x): float
    {
        if ($x < 0.5) {
            if ($x <= 0.0 && floor($x) === $x) {
                return INF;
            }

            return M_PI / (sin(M_PI * $x) * self::gamma(1.0 - $x));
        }

        $z = $x - 1.0;
        $acc = self::LANCZOS_COEFFICIENTS[0];

        for ($i = 1, $count = count(self::LANCZOS_COEFFICIENTS); $i < $count; $i++) {
            $acc += self::LANCZOS_COEFFICIENTS[$i] / ($z + $i);
        }

        $t = $z + self::LANCZOS_G + 0.5;

        return sqrt(2.0 * M_PI) * ($t ** ($z + 0.5)) * exp(-$t) * $acc;
    }
}