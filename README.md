# LoadedDice

LoadedDice is a PHP library for deterministic pseudo-random utilities, reproducible probability distributions, multi-armed bandit optimization algorithms, consistent rendezvous hashing, and seamless Laravel integration.

Given the same secret key and input arguments, LoadedDice guarantees reproducible outcomes, enabling consistent A/B testing, deterministic simulations, and optimal resource allocation across distributed environments.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Multi-Armed Bandits](#multi-armed-bandits)
  - [Core Workflow](#core-workflow)
  - [Bandit Strategies](#bandit-strategies)
  - [Strategy Decorators](#strategy-decorators)
- [Probability Distributions](#probability-distributions)
  - [Continuous Distributions](#continuous-distributions)
  - [Discrete Distributions](#discrete-distributions)
- [Rendezvous Hashing](#rendezvous-hashing)
  - [Simple Rendezvous](#simple-rendezvous)
  - [Weighted Rendezvous](#weighted-rendezvous)
- [Deterministic Random Oracle](#deterministic-random-oracle)
- [Laravel Integration](#laravel-integration)
- [Mathematical Utilities](#mathematical-utilities)
- [Testing](#testing)
- [License](#license)

---

## Features

- **Multi-Armed Bandits:** High-performance reinforcement learning algorithms (`ThompsonSamplingBeta`, `UcbOne`, `ExpThree`, `EpsilonGreedy`, `Greedy`) with decorator modifiers (`ActionElimination`, `EpsilonFallthrough`).
- **Statistical Distributions:** Full support for continuous (`Beta`, `Exponential`, `Gamma`, `Normal`, `Uniform`) and discrete (`Binomial`, `Geometric`, `Hypergeometric`, `Poisson`, `Uniform`) distributions with `random()`, `cdf()`, and `pdf()`/`pmf()` evaluations.
- **Consistent Rendezvous Hashing:** Highest Random Weight (HRW) hashing for distributed node selection with uniform and weighted capacity routing.
- **Deterministic Random Oracle:** Map arbitrary stringable keys and objects to reproducible floats, booleans, and integers.
- **Laravel Ready:** Includes service provider auto-discovery, configuration publishing, facade, and `Collection::fauxRandom()` / `Collection::rendezvous()` macros.

---

## Requirements

- PHP 8.4 or higher

---

## Installation

Install the package via Composer:

```bash
composer require ignescent/loaded-dice
```

---

## Multi-Armed Bandits

Multi-armed bandits balance exploration (discovering high-reward options) and exploitation (capitalizing on the best-known option) to maximize cumulative rewards over time.

### Core Workflow

Track historical rewards in `ExperimentalData`, instantiate your strategy, and select arms via `Bandit`:

```php
use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\ExperimentalData;
use Ignescent\LoadedDice\Bandits\ThompsonSamplingBeta;

$data = new ExperimentalData();
$data->initializeArms(['layout_a', 'layout_b', 'layout_c']);

// Initialize bandit with Thompson Sampling
$bandit = new Bandit(new ThompsonSamplingBeta(), $data);

// Choose an arm to display to the user
$chosenArm = $bandit->pull();

// Record feedback/conversion (e.g., 1.0 for conversion, 0.0 for bounce)
$bandit->update($chosenArm, 1.0);
```

### Bandit Strategies

| Strategy | Class | Description |
|---|---|---|
| **Thompson Sampling (Beta)** | `ThompsonSamplingBeta` | Bayesian strategy drawing samples from posterior $\text{Beta}(\alpha, \beta)$ distributions. Ideal for conversion rate optimization. |
| **UCB1** | `UcbOne` | Upper Confidence Bound algorithm balancing empirical mean with an uncertainty bonus ($O(\sqrt{\ln T / N_i})$). |
| **Exp3** | `ExpThree` | Exponential-weight exploration and exploitation with optimal closed-form exploration decay $\gamma = \min\left(1, \sqrt{\frac{K \ln K}{(e - 1) T}}\right)$. |
| **$\epsilon$-Greedy** | `EpsilonGreedy` | Selects a random arm with probability $\epsilon$ (default $0.1$) and the best empirical arm with probability $1 - \epsilon$. |
| **Greedy** | `Greedy` | Pure exploitation strategy selecting the arm with the highest empirical mean. |

#### Example: UCB1 and Exp3

```php
use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\ExpThree;
use Ignescent\LoadedDice\Bandits\UcbOne;

// UCB1 strategy
$ucbBandit = new Bandit(new UcbOne(), $data);

// Exp3 strategy (adaptive closed-form exploration)
$expThreeBandit = new Bandit(new ExpThree(), $data);
```

### Strategy Decorators

Decorators wrap existing bandit strategies to provide advanced meta-policies:

#### Early Action Elimination (`ActionElimination`)
Prunes arms whose upper confidence bound (UCB) falls below the best arm's lower confidence bound (LCB), concentrating pulls strictly on non-dominated candidates:

```php
use Ignescent\LoadedDice\Bandits\ActionElimination;
use Ignescent\LoadedDice\Bandits\Bandit;
use Ignescent\LoadedDice\Bandits\Greedy;

$strategy = new ActionElimination(new Greedy());
$bandit = new Bandit($strategy, $data);
```

#### $\epsilon$-Fallthrough (`EpsilonFallthrough`)
Wraps any underlying strategy with a fixed uniform exploration rate $\epsilon$:

```php
use Ignescent\LoadedDice\Bandits\EpsilonFallthrough;
use Ignescent\LoadedDice\Bandits\Greedy;

$strategy = new EpsilonFallthrough(new Greedy(), epsilon: 0.15);
```

---

## Probability Distributions

Every distribution provides methods to generate random variates (`random()`), calculate cumulative distribution functions (`cdf()`), and compute density/mass functions (`pdf()` or `pmf()`).

Passing a `Random\Randomizer` is optional; if omitted, a default `new Randomizer()` is utilized.

### Continuous Distributions

```php
use Ignescent\LoadedDice\Distributions\Continuous\Beta;
use Ignescent\LoadedDice\Distributions\Continuous\Exponential;
use Ignescent\LoadedDice\Distributions\Continuous\Gamma;
use Ignescent\LoadedDice\Distributions\Continuous\Normal;
use Ignescent\LoadedDice\Distributions\Continuous\Uniform;

// Normal distribution: N(mean, stdDev)
$normal = new Normal(mean: 0.0, stdDev: 1.0);
$sample = $normal->random(); // e.g. 0.421
$cdf    = $normal->cdf(1.96); // ~0.975
$pdf    = $normal->pdf(0.0);  // ~0.3989

// Beta distribution: Beta(alpha, beta)
$beta = new Beta(alpha: 2.0, beta: 5.0);
$sample = $beta->random();
$cdf    = $beta->cdf(0.3);
$pdf    = $beta->pdf(0.3);

// Exponential distribution: Exp(lambda)
$exponential = new Exponential(lambda: 1.5);
$sample = $exponential->random();
$cdf    = $exponential->cdf(2.0);
$pdf    = $exponential->pdf(2.0);

// Gamma distribution: Gamma(shape, scale)
$gamma = new Gamma(shape: 3.0, scale: 2.0);
$sample = $gamma->random();
$cdf    = $gamma->cdf(4.0);
$pdf    = $gamma->pdf(4.0);

// Continuous Uniform: U(min, max)
$uniform = new Uniform(min: 10.0, max: 20.0);
$sample = $uniform->random();
$cdf    = $uniform->cdf(15.0); // 0.5
$pdf    = $uniform->pdf(15.0); // 0.1
```

### Discrete Distributions

```php
use Ignescent\LoadedDice\Distributions\Discrete\Binomial;
use Ignescent\LoadedDice\Distributions\Discrete\Geometric;
use Ignescent\LoadedDice\Distributions\Discrete\Hypergeometric;
use Ignescent\LoadedDice\Distributions\Discrete\Poisson;
use Ignescent\LoadedDice\Distributions\Discrete\Uniform;

// Binomial distribution: Binomial(trials, probability)
$binomial = new Binomial(trials: 10, probability: 0.5);
$successes = $binomial->random(); // int [0, 10]
$cdf       = $binomial->cdf(5);
$pmf       = $binomial->pmf(5);

// Hypergeometric distribution: Hypergeometric(population, successes, draws)
// Models sampling without replacement (e.g. drawing aces from a standard card deck)
$hypergeometric = new Hypergeometric(population: 52, successes: 4, draws: 5);
$observedAces   = $hypergeometric->random(); // int [0, 4]
$cdf            = $hypergeometric->cdf(1);
$pmf            = $hypergeometric->pmf(1);

// Geometric distribution: Geometric(probability)
// Represents the number of failures before the first success (0-indexed)
$geometric = new Geometric(probability: 0.2);
$failures  = $geometric->random();
$cdf       = $geometric->cdf(2);
$pmf       = $geometric->pmf(2);

// Poisson distribution: Poisson(lambda)
$poisson = new Poisson(lambda: 4.0);
$count   = $poisson->random();
$cdf     = $poisson->cdf(3);
$pmf     = $poisson->pmf(3);

// Discrete Uniform: Uniform(min, max)
$discreteUniform = new Uniform(min: 1, max: 6);
$roll = $discreteUniform->random(); // int [1, 6]
$cdf  = $discreteUniform->cdf(3);   // 0.5
$pmf  = $discreteUniform->pmf(3);   // 0.1667
```

---

## Rendezvous Hashing

Rendezvous hashing (Highest Random Weight hashing) provides consistent, deterministic routing of keys across a cluster of nodes. When sites are added or removed, only a minimal fraction of keys require reassignment.

### Simple Rendezvous

Uniformly routes keys across identical sites:

```php
use Ignescent\LoadedDice\Facade;
use Ignescent\LoadedDice\Hashing\Hmac;
use Ignescent\LoadedDice\Rendezvous\Simple;

$hash = new Hmac('user_session:10492');
$rendezvous = new Simple($hash);

$servers = ['server-1', 'server-2', 'server-3', 'server-4'];

// Route a specific object or user ID to a server
$targetServer = $rendezvous->randomElement($servers);

// Select top 2 servers for replication
$replicaServers = $rendezvous->randomSubset(2, $servers);
```

### Weighted Rendezvous

Routes keys across heterogeneous nodes proportional to their capacity weights:

```php
use Ignescent\LoadedDice\Facade;

$nodes = [
    'cache-node-1' => 10.0, // High capacity
    'cache-node-2' => 5.0,  // Medium capacity
    'cache-node-3' => 1.0,  // Low capacity
];

// Determine the primary node for a cache key
$assignedNodes = Facade::weightedRendezvous(
    objectIdentifier: 'user_session:10492',
    weightedSites: $nodes,
    siteCount: 1
);
// Returns: ['cache-node-1']
```

---

## Deterministic Random Oracle

The `RandomOracle` evaluates pseudo-random values deterministically from arbitrary string identifiers:

```php
use Ignescent\LoadedDice\Facade;
use Ignescent\LoadedDice\Hashing\Hmac;

$facade = new Facade(new Hmac('app-secret'));

// Deterministic boolean coin-toss
$isEnabled = $facade->boolean('user-id-4815162342');

// Deterministic float in [0, 1)
$score = $facade->float('document-id-9912');

// Deterministic integer in [0, max)
$bucket = $facade->integer('visitor-session-id', 100);
```

---

## Laravel Integration

LoadedDice includes first-class integration for Laravel applications.

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=loaded_dice
```

This registers `config/loaded_dice.php`:

```php
return [
    'key' => env('LOADED_DICE_KEY', 'St. Antiochus\' die'),
];
```

### Laravel Facade

Access deterministic helpers directly via the `LoadedDice` facade:

```php
use Ignescent\LoadedDice\Laravel\LoadedDice;

// Deterministic dice roll from user ID
$bucket = LoadedDice::integer($user->id, 10);

// Deterministic item selection
$assignedServer = LoadedDice::randomElement(['node-a', 'node-b', 'node-c']);
```

### Collection Macros

LoadedDice registers macros on Laravel's `Collection` for deterministic pseudo-random sampling and consistent rendezvous hashing.

#### `Collection::fauxRandom()`

Performs deterministic sampling seeded by the application key:

```php
$collection = collect(['Variant A', 'Variant B', 'Variant C']);

// Select single deterministic element
$variant = $collection->fauxRandom();

// Select deterministic subset of elements
$subset = $collection->fauxRandom(2);
```

#### `Collection::rendezvous()`

Deterministically routes a key/identifier across items in the collection using Highest Random Weight (HRW) rendezvous hashing. This provides minimal disruption when nodes are added or removed:

```php
$servers = collect(['cache-1.internal', 'cache-2.internal', 'cache-3.internal', 'cache-4.internal']);

// Uniform rendezvous: deterministically route a user or session ID to a single server
$targetServer = $servers->rendezvous(objectIdentifier: 'session:user_10492');
// e.g. 'cache-2.internal'

// Subset rendezvous: select the top N servers for primary and backup replication
$replicationNodes = $servers->rendezvous(
    objectIdentifier: 'document:order_88219',
    number: 2
);
// Returns a new Collection: ['cache-3.internal', 'cache-1.internal']

// Weighted rendezvous: route across heterogeneous servers proportional to capacity weights
$nodes = collect(['worker-small', 'worker-medium', 'worker-large']);
$weights = [
    // 'worker-small'  => 1.0, // Missing weights default to 1.0.
    'worker-medium' => 5.0,
    'worker-large'  => 20.0,
];

$assignedWorker = $nodes->rendezvous(
    objectIdentifier: 'job:process_video_441',
    weightedSites: $weights
);
// Higher probability of routing to 'worker-large' proportional to its weight
```

---

## Mathematical Utilities

LoadedDice incorporates optimized mathematical routines in `Ignescent\LoadedDice\Utils` with high-precision double arithmetic:

- **Gamma Functions:** `Utils::gamma`, `Utils::logGamma`, `Utils::incompleteLowerGamma`, `Utils::incompleteUpperGamma`, `Utils::regularizedGamma` (Lanczos approximation with reflection formula and modified Lentz continued fractions).
- **Beta Functions:** `Utils::beta`, `Utils::logBeta`, `Utils::regularizedIncompleteBeta`.
- **Error Function:** `Utils::erf` (Hastings approximation).

---

## Testing

Run the test suite using PHPUnit:

```bash
./vendor/bin/phpunit
```

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
