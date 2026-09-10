<?php

namespace Helper;

class Benchmark extends \Codeception\Module
{
    private string $resultsDir = __DIR__ . '/../_output/benchmarks';

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
     * Save benchmark results to a JSON file
     */
    public function saveResults(array $results, string $filename): string
    {
        if (!is_dir($this->resultsDir)) {
            mkdir($this->resultsDir, 0755, true);
        }

        $filepath = $this->resultsDir . '/' . $filename . '.json';
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => $results,
        ];

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $output = "\n✓ Benchmark results saved to: $filepath\n";
        fwrite(STDERR, $output);

        return $filepath;
    }

    /**
     * Load and compare benchmark results
     */
    public function compareResults(array $current, string $baselineFilename): void
    {
        $filepath = $this->resultsDir . '/' . $baselineFilename . '.json';

        if (!file_exists($filepath)) {
            $output = "\n⚠ Baseline file not found: $filepath\n";
            fwrite(STDERR, $output);
            return;
        }

        $baseline = json_decode(file_get_contents($filepath), true);
        $baselineResults = $baseline['results'] ?? [];

        if (empty($baselineResults)) {
            $output = "\n⚠ No results in baseline file\n";
            fwrite(STDERR, $output);
            return;
        }

        $output = "\n" . str_repeat("=", 200) . "\n";
        $output .= "PERFORMANCE COMPARISON: Current vs Baseline\n";
        $output .= "Baseline timestamp: " . $baseline['timestamp'] . "\n";
        $output .= str_repeat("=", 200) . "\n";

        $output .= sprintf(
            "| %-20s | %-12s | %-12s | %-14s | %-12s | %-12s | %-14s | %-10s |\n",
            "Column",
            "Baseline Avg",
            "Current Avg",
            "Avg Change",
            "Baseline P95",
            "Current P95",
            "P95 Change",
            "Status"
        );
        $output .= str_repeat("-", 200) . "\n";

        $totalDegradation = 0;
        $totalImprovement = 0;

        foreach ($current as $idx => $row) {
            $baseline_row = $baselineResults[$idx] ?? null;

            if (!$baseline_row) {
                $status = "⚠ NEW";
                $avg_change = "N/A";
                $p95_change = "N/A";
                $baseline_avg = "N/A";
                $baseline_p95 = "N/A";
            } else {
                $baseline_avg = sprintf("%.4f", $baseline_row['avg_ms']);
                $baseline_p95 = sprintf("%.4f", $baseline_row['p95']);
                
                $avg_diff = $row['avg_ms'] - $baseline_row['avg_ms'];
                $avg_change_pct = ($avg_diff / $baseline_row['avg_ms']) * 100;
                $avg_change = sprintf("%+.2f%% (%+.4f)", $avg_change_pct, $avg_diff);

                $p95_diff = $row['p95'] - $baseline_row['p95'];
                $p95_change_pct = ($p95_diff / $baseline_row['p95']) * 100;
                $p95_change = sprintf("%+.2f%% (%+.4f)", $p95_change_pct, $p95_diff);

                if ($avg_change_pct > 5) {
                    $status = "🔴 SLOWER";
                    $totalDegradation += $avg_change_pct;
                } elseif ($avg_change_pct < -5) {
                    $status = "🟢 FASTER";
                    $totalImprovement += abs($avg_change_pct);
                } else {
                    $status = "🟡 SIMILAR";
                }
            }

            $output .= sprintf(
                "| %-20s | %-12s | %-12.4f | %-14s | %-12s | %-12.4f | %-14s | %-10s |\n",
                substr($row['column'], 0, 20),
                $baseline_avg,
                $row['avg_ms'],
                $avg_change,
                $baseline_p95,
                $row['p95'],
                $p95_change,
                $status
            );
        }

        $output .= str_repeat("=", 200) . "\n";
        $output .= sprintf(
            "Summary: Total Degradation: %.2f%% | Total Improvement: %.2f%%\n",
            $totalDegradation,
            $totalImprovement
        );
        $output .= str_repeat("=", 200) . "\n\n";

        fwrite(STDERR, $output);
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
