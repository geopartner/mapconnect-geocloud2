<?php

namespace Helper;

class Benchmark extends \Codeception\Module
{
    /**
     * Run a function multiple times and return timing statistics
     *
     * @param callable $callable The function to benchmark
     * @param int $iterations Number of times to run the function
     * @param mixed ...$args Arguments to pass to the function
     * @return array {totalMs, averageMs, iterations}
     */
    public function benchmarkFunction(callable $callable, int $iterations = 100, ...$args): array
    {
        $startTime = hrtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $callable(...$args);
        }

        $endTime = hrtime(true);
        $totalTime = ($endTime - $startTime) / 1_000_000; // Convert to ms

        return [
            'totalMs' => round($totalTime, 4),
            'averageMs' => round($totalTime / $iterations, 4),
            'iterations' => $iterations,
        ];
    }

    /**
     * Print benchmark results in a formatted way
     */
    public function printBenchmarkResults(string $name, array $results): void
    {
        echo "\n";
        echo "=== Benchmark: {$name} ===\n";
        echo "Iterations: " . $results['iterations'] . "\n";
        echo "Total Time: " . $results['totalMs'] . "ms\n";
        echo "Average Time per call: " . $results['averageMs'] . "ms\n";
        echo "========================\n";
    }
}
