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
     * @return array {totalMs, averageMs, iterations, fastest, slowest, p95, first}
     */
    public function benchmarkFunction(callable $callable, int $iterations = 100, ...$args): array
    {
        $times = []; // Individual times in ms
        $startTime = hrtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $iterStart = hrtime(true);
            $callable(...$args);
            $iterEnd = hrtime(true);
            $times[] = ($iterEnd - $iterStart) / 1_000_000; // Convert to ms
        }

        $endTime = hrtime(true);
        $totalTime = ($endTime - $startTime) / 1_000_000; // Convert to ms

        // Calculate statistics
        sort($times);
        $fastest = min($times);
        $slowest = max($times);
        $first = $times[0]; // First iteration time
        $p95_index = (int)(0.95 * count($times)) - 1;
        $p95 = $times[$p95_index] ?? end($times);

        return [
            'totalMs' => round($totalTime, 4),
            'averageMs' => round($totalTime / $iterations, 4),
            'iterations' => $iterations,
            'fastest' => round($fastest, 4),
            'slowest' => round($slowest, 4),
            'p95' => round($p95, 4),
            'first' => round($first, 4),
        ];
    }

    /**
     * Print benchmark results in a formatted table
     */
    public function printBenchmarkTable(array $results, string $database, string $layer): void
    {
        $output = "\n";
        $output .= str_repeat("=", 140) . "\n";
        $output .= "Database: $database | Layer: $layer\n";
        $output .= str_repeat("=", 140) . "\n";
        
        // Table header
        $output .= sprintf(
            "| %-25s | %-10s | %-10s | %-10s | %-10s | %-10s | %-10s | %-10s |\n",
            "Column",
            "Avg (ms)",
            "First (ms)",
            "P95 (ms)",
            "Fastest (ms)",
            "Slowest (ms)",
            "Total (ms)",
            "Iterations"
        );
        $output .= str_repeat("-", 140) . "\n";
        
        // Table rows
        foreach ($results as $row) {
            $output .= sprintf(
                "| %-25s | %-10.4f | %-10.4f | %-10.4f | %-10.4f | %-10.4f | %-10.4f | %-10d |\n",
                substr($row['column'], 0, 25),
                $row['avg_ms'],
                $row['first'],
                $row['p95'],
                $row['fastest'],
                $row['slowest'],
                $row['total_ms'],
                $row['iterations']
            );
        }
        
        $output .= str_repeat("=", 140) . "\n\n";
        
        // Write to stderr to ensure display in test output
        fwrite(STDERR, $output);
    }

    /**
     * Print benchmark results in a formatted way
     */
    public function printBenchmarkResults(string $name, array $results): void
    {
        $output = "\n";
        $output .= "=== Benchmark: {$name} ===\n";
        $output .= "Iterations: " . $results['iterations'] . "\n";
        $output .= "Total Time: " . $results['totalMs'] . "ms\n";
        $output .= "Average Time per call: " . $results['averageMs'] . "ms\n";
        $output .= "========================\n";
        
        // Write to stderr to ensure display in test output
        fwrite(STDERR, $output);
    }
}
