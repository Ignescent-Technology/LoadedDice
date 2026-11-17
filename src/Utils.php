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

    private const float HASTINGS_P = 0.3275911;

    private const array HASTINGS_COEFFICIENTS = [
        0.254829592,
        -0.284496736,
        1.421413741,
        -1.453152027,
        1.061405429,
    ];

    /**
     * Computes the error function erf(x) using the Hastings approximation.
     *
     * @param float $x
     * @return float
     */
    public static function erf(float $x): float
    {
        if ($x === 0.0) {
            return 0.0;
        }

        $sign = $x < 0.0 ? -1.0 : 1.0;
        $x = abs($x);

        $t = 1.0 / (1.0 + self::HASTINGS_P * $x);

        $poly = (
            (
                (
                    (
                        self::HASTINGS_COEFFICIENTS[4]
                        * $t + self::HASTINGS_COEFFICIENTS[3]
                    )
                    * $t + self::HASTINGS_COEFFICIENTS[2]
                ) * $t + self::HASTINGS_COEFFICIENTS[1]
            ) * $t + self::HASTINGS_COEFFICIENTS[0]) * $t;

        return $sign * (1.0 - $poly * exp(-$x * $x));
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

        return sqrt(2.0 * M_PI) * exp(($z + 0.5) * log($t) - $t) * $acc;
    }

    /**
     * Computes the natural logarithm of the gamma function for a positive input.
     *
     * @param float $x The input value (must be > 0).
     * @return float The natural logarithm of Gamma(x).
     */
    public static function logGamma(float $x): float
    {
        if ($x <= 0.0) {
            return NAN;
        }

        if ($x < 0.5) {
            return log(M_PI) - log(sin(M_PI * $x)) - self::logGamma(1.0 - $x);
        }

        $z = $x - 1.0;
        $acc = self::LANCZOS_COEFFICIENTS[0];

        for ($i = 1, $count = count(self::LANCZOS_COEFFICIENTS); $i < $count; $i++) {
            $acc += self::LANCZOS_COEFFICIENTS[$i] / ($z + $i);
        }

        $t = $z + self::LANCZOS_G + 0.5;

        return 0.5 * log(2.0 * M_PI) + ($z + 0.5) * log($t) - $t + log($acc);
    }

    /**
     * Computes the lower incomplete gamma function \gamma(s, x) = \int_0^x t^{s-1} e^{-t} dt.
     *
     * @param float $s The shape parameter (must be > 0).
     * @param float $x The upper limit of integration (must be >= 0).
     * @return float The computed value of the lower incomplete gamma function.
     */
    public static function incompleteLowerGamma(float $s, float $x): float
    {
        if ($s <= 0.0 || $x < 0.0) {
            return NAN;
        }

        if ($x === 0.0) {
            return 0.0;
        }

        if ($x < $s + 1.0) {
            return self::incompleteGammaSeries($s, $x);
        }

        return max(0.0, self::gamma($s) - self::incompleteGammaContinuedFraction($s, $x));
    }

    /**
     * Computes the upper incomplete gamma function \Gamma(s, x) = \int_x^\infty t^{s-1} e^{-t} dt.
     *
     * @param float $s The shape parameter (must be > 0).
     * @param float $x The lower limit of integration (must be >= 0).
     * @return float The computed value of the upper incomplete gamma function.
     */
    public static function incompleteUpperGamma(float $s, float $x): float
    {
        if ($s <= 0.0 || $x < 0.0) {
            return NAN;
        }

        if ($x === 0.0) {
            return self::gamma($s);
        }

        if ($x < $s + 1.0) {
            return max(0.0, self::gamma($s) - self::incompleteGammaSeries($s, $x));
        }

        return self::incompleteGammaContinuedFraction($s, $x);
    }

    private static function incompleteGammaSeries(float $s, float $x): float
    {
        $sum = 1.0 / $s;
        $term = $sum;
        for ($k = 1; $k <= 200; $k++) {
            $term *= $x / ($s + $k);
            $sum += $term;
            if (abs($term) < abs($sum) * 1e-15) {
                break;
            }
        }

        return exp($s * log($x) - $x) * $sum;
    }

    private static function incompleteGammaContinuedFraction(float $s, float $x): float
    {
        $b = $x + 1.0 - $s;
        $c = 1.0 / 1e-30;
        $d = 1.0 / $b;
        $h = $d;

        for ($i = 1; $i <= 200; $i++) {
            $an = -$i * ($i - $s);
            $b += 2.0;
            $d = $an * $d + $b;
            if (abs($d) < 1e-30) {
                $d = 1e-30;
            }
            $c = $b + $an / $c;
            if (abs($c) < 1e-30) {
                $c = 1e-30;
            }
            $d = 1.0 / $d;
            $del = $d * $c;
            $h *= $del;
            if (abs($del - 1.0) < 1e-15) {
                break;
            }
        }

        return exp($s * log($x) - $x) * $h;
    }

    /**
     * Computes the regularized lower incomplete gamma function P(s, x) = \gamma(s, x) / \Gamma(s).
     *
     * @param float $s The shape parameter (must be > 0).
     * @param float $x The upper limit of integration (must be >= 0).
     * @return float The computed regularized gamma value in the range [0, 1].
     */
    public static function regularizedGamma(float $s, float $x): float
    {
        if ($s <= 0.0 || $x < 0.0) {
            return NAN;
        }

        if ($x === 0.0) {
            return 0.0;
        }

        $gammaS = self::gamma($s);
        if ($gammaS == 0.0 || is_nan($gammaS) || is_infinite($gammaS)) {
            return NAN;
        }

        if ($x < $s + 1.0) {
            return min(1.0, max(0.0, self::incompleteGammaSeries($s, $x) / $gammaS));
        }

        return min(1.0, max(0.0, 1.0 - (self::incompleteGammaContinuedFraction($s, $x) / $gammaS)));
    }

    /**
     * Computes the regularized incomplete beta function I_x(a, b) = B(x; a, b) / B(a, b).
     *
     * @param float $a The first shape parameter (must be > 0).
     * @param float $b The second shape parameter (must be > 0).
     * @param float $x The upper limit of integration (must be in [0, 1]).
     * @return float The computed regularized incomplete beta value in the range [0, 1].
     */
    public static function regularizedIncompleteBeta(float $a, float $b, float $x): float
    {
        if ($a <= 0.0 || $b <= 0.0 || $x < 0.0 || $x > 1.0) {
            return NAN;
        }

        if ($x === 0.0) {
            return 0.0;
        }

        if ($x === 1.0) {
            return 1.0;
        }

        if ($x > ($a + 1.0) / ($a + $b + 2.0)) {
            return 1.0 - self::regularizedIncompleteBeta($b, $a, 1.0 - $x);
        }

        $front = exp($a * log($x) + $b * log(1.0 - $x)) / ($a * self::beta($a, $b));

        return min(1.0, max(0.0, $front * self::incompleteBetaContinuedFraction($a, $b, $x)));
    }

    private static function incompleteBetaContinuedFraction(float $a, float $b, float $x): float
    {
        $c = 1.0;
        $d = 1.0 - ($a + $b) * $x / ($a + 1.0);
        if (abs($d) < 1e-30) {
            $d = 1e-30;
        }
        $d = 1.0 / $d;
        $h = $d;

        for ($m = 1; $m <= 200; $m++) {
            $m2 = 2 * $m;

            // Even step
            $num = $m * ($b - $m) * $x / (($a + $m2 - 1.0) * ($a + $m2));
            $d = 1.0 + $num * $d;
            if (abs($d) < 1e-30) {
                $d = 1e-30;
            }
            $c = 1.0 + $num / $c;
            if (abs($c) < 1e-30) {
                $c = 1e-30;
            }
            $d = 1.0 / $d;
            $h *= $d * $c;

            // Odd step
            $num = -($a + $m) * ($a + $b + $m) * $x / (($a + $m2) * ($a + $m2 + 1.0));
            $d = 1.0 + $num * $d;
            if (abs($d) < 1e-30) {
                $d = 1e-30;
            }
            $c = 1.0 + $num / $c;
            if (abs($c) < 1e-30) {
                $c = 1e-30;
            }
            $d = 1.0 / $d;
            $del = $d * $c;
            $h *= $del;

            if (abs($del - 1.0) < 1e-15) {
                break;
            }
        }

        return $h;
    }

    /**
     * Computes the beta function for two given inputs.
     *
     * The beta function is a special function in mathematics that is closely related
     * to the gamma function. It is defined in terms of the gamma function using the formula:
     * B(a, b) = Γ(a) * Γ(b) / Γ(a + b).
     *
     * @param float $a The first input value for which the beta function is calculated.
     *                 Must be a positive number.
     * @param float $b The second input value for which the beta function is calculated.
     *                 Must be a positive number.
     * @return float The computed value of the beta function for the given inputs.
     */
    public static function beta(float $a, float $b): float
    {
        return self::gamma($a) * self::gamma($b) / self::gamma($a + $b);
    }
}