# Changelog

All notable changes to `ignescent/loaded-dice` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-11-18

### Added
- **Multi-Armed Bandits:**
  - Core bandit orchestrator (`Bandit`) with experimental history tracking (`ExperimentalData`).
  - Bandit selection strategies: `ThompsonSamplingBeta`, `UcbOne`, `ExpThree`, `EpsilonGreedy`, and `Greedy`.
  - Strategy decorators: `ActionElimination` (confidence bound pruning) and `EpsilonFallthrough` (exploration mixing).
- **Statistical Distributions:**
  - Continuous distributions: `Beta`, `Exponential`, `Gamma`, `Normal`, and `Uniform` with `random()`, `cdf()`, and `pdf()`.
  - Discrete distributions: `Binomial`, `Geometric`, `Poisson`, and `Uniform` with `random()`, `cdf()`, and `pmf()`.
  - Optional `Randomizer` parameter injection across all distribution constructors with default fallback to `new Randomizer()`.
- **Rendezvous Hashing:**
  - Highest Random Weight (HRW) consistent hashing via `Simple` and `Weighted` algorithms.
  - Multi-site subset selection with key preservation and callable count resolution.
- **Deterministic Random Oracle & Hashing:**
  - `RandomOracle` for reproducible mapping of arbitrary stringable keys and objects to floats, booleans, and integers.
  - Hashing abstractions including `Hash` and `Hmac`.
- **Mathematical Utilities:**
  - High-precision implementations in `Utils`: `gamma` (Lanczos approximation), `logGamma`, `incompleteLowerGamma`, `incompleteUpperGamma`, `regularizedGamma`, `beta`, `regularizedIncompleteBeta`, `erf` (Hastings approximation), `erfc`, and `combination`.
- **Laravel Framework Integration:**
  - Package auto-discovery for `LoadedDiceProvider` and `LoadedDice` facade.
  - Configuration publishing via `config/loaded_dice.php`.
  - `Collection::fauxRandom()` macro for deterministic pseudo-random sampling.
  - `Collection::rendezvous()` macro for consistent node routing across collections.
