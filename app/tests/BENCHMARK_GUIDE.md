# Benchmark Suite Guide

## Overview

The benchmark suite allows you to test the performance of the `Layer::getValueFromKey()` function and save/compare results across code changes.

## Running Benchmarks

### 1. Run Benchmark (View Results)

```bash
cd /var/www/geocloud2/app
./vendor/bin/codecept run benchmark LayerBenchmarkTest --verbose 2>&1
```

This displays results in a formatted table with:
- **Avg (ms)** - Average execution time
- **First (ms)** - First iteration time (often slower due to cold start)
- **P95 (ms)** - 95th percentile (tail latency)
- **Fastest (ms)** - Best case execution time
- **Slowest (ms)** - Worst case execution time
- **Total (ms)** - Total time for all iterations
- **Iterations** - Number of times the function was run

### 2. Save Baseline Results

Run the benchmark and save results to a file:

```bash
BENCHMARK_SAVE=baseline ./vendor/bin/codecept run benchmark LayerBenchmarkTest --verbose 2>&1
```

This saves results to: `app/tests/_output/benchmarks/baseline.json`

You can use any name instead of `baseline`:

```bash
BENCHMARK_SAVE=before_optimization ./vendor/bin/codecept run benchmark LayerBenchmarkTest --verbose 2>&1
BENCHMARK_SAVE=after_optimization ./vendor/bin/codecept run benchmark LayerBenchmarkTest --verbose 2>&1
```

### 3. Compare Against Baseline

After making code changes, compare new results against the saved baseline:

```bash
BENCHMARK_COMPARE=baseline ./vendor/bin/codecept run benchmark LayerBenchmarkTest --verbose 2>&1
```

This generates a comparison report showing:
- ✓ ✓ Baseline timestamp
- % Change in average execution time
- % Change in P95 latency
- Status indicators:
  - 🟢 **FASTER** - Performance improved (>5% faster)
  - 🟡 **SIMILAR** - Minor change (±5%)
  - 🔴 **SLOWER** - Performance degraded (>5% slower)
  - ⚠ **NEW** - New test not in baseline

### 4. Workflow Example

```bash
# Step 1: Establish baseline before changes
BENCHMARK_SAVE=production_baseline ./vendor/bin/codecept run benchmark LayerBenchmarkTest --verbose 2>&1

# Step 2: Make code optimizations to Layer.php

# Step 3: Compare results
BENCHMARK_COMPARE=production_baseline ./vendor/bin/codecept run benchmark LayerBenchmarkTest --verbose 2>&1

# Step 4: If satisfied, save as new baseline
BENCHMARK_SAVE=production_baseline ./vendor/bin/codecept run benchmark LayerBenchmarkTest --verbose 2>&1
```

## Interpreting Results

### Good Signs
- Average time is consistent (not increasing)
- P95 close to average (no tail latency issues)
- First iteration similar to average (no cold start penalty)
- Fastest close to average (no outliers)

### Warning Signs
- Slowest >> Average (inconsistent performance)
- P95 >> Average (tail latency issues)
- First >> Average (cold start overhead)
- Degradation > 5% (performance regression)

## Tips

1. **Warmup runs**: First iteration is often slower; look at average time, not first
2. **Multiple baselines**: Keep historical baselines for comparison
3. **Same environment**: Compare results from same hardware/database state
4. **Sample size**: 100 iterations is good; increase for more accurate P95
5. **Monitor trends**: Track performance over time across multiple releases
