<?php

namespace app\tests\benchmark;

use app\inc\Connection;
use app\models\Layer;
use Codeception\Test\Unit;

class LayerBenchmarkTest extends Unit
{
    /**
     * @var \BenchmarkTester
     */
    protected $tester;

    /**
     * @var Layer
     */
    protected $layer;

    /**
     * Columns to test on all layers
     */
    protected array $columnsToTest = [
        'privileges', // only in table
        'coord_dimension', // only in view
        'f_table_schema', // can be deduced from parameter
    ];

    /**
     * Number of iterations for all benchmarks
     */
    protected int $iterations = 100;

    protected function _before(): void
    {
        $this->layer = new Layer();
    }

    /**
     * Define layers to benchmark with their database
     * 
     * Format: array of [$database, $layerKey, $comment]
     */
    public function layerProvider(): array
    {
        return [
            // EDIT BELOW: Add your layers with database here
            // Format: [database_name, layer_key, comment]
            ['e2e', 'public.test_flade.the_geom', 'public.test_flade.the_geom'],
        ];
    }

    /**
     * Benchmark Layer::getValueFromKey() with data provider
     * 
     * @dataProvider layerProvider
     */
    public function testGetValueFromKeyWithLayers($database, $layerKey, $comment): void
    {
        $dbLabel = $database ?? 'default';
        
        $commentLabel = $comment ? " ({$comment})" : '';
        $this->tester->wantTo("Benchmark Layer::getValueFromKey() for key: $layerKey on database: $dbLabel{$commentLabel}");

        // Create layer with specific database connection if provided
        if ($database) {
            $connection = new Connection(database: $database);
            $layer = new Layer(connection: $connection);
        } else {
            $layer = $this->layer;
        }

        // Verify the layer exists
        try {
            $layer->getValueFromKey($layerKey, $this->columnsToTest[0]);
        } catch (\Exception $e) {
            $this->markTestSkipped("Layer key '$layerKey' not found in database '$dbLabel': " . $e->getMessage());
        }

        // Collect benchmark results
        $results = [];
        foreach ($this->columnsToTest as $column) {
            $benchmarkResult = $this->tester->benchmarkFunction(
                function () use ($layer, $layerKey, $column) {
                    $layer->getValueFromKey($layerKey, $column);
                },
                $this->iterations
            );

            $results[] = [
                'database' => $dbLabel,
                'layer' => $comment ?: $layerKey,
                'column' => $column,
                'iterations' => $benchmarkResult['iterations'],
                'total_ms' => $benchmarkResult['totalMs'],
                'avg_ms' => $benchmarkResult['averageMs'],
                'fastest' => $benchmarkResult['fastest'],
                'slowest' => $benchmarkResult['slowest'],
                'p95' => $benchmarkResult['p95'],
                'first' => $benchmarkResult['first'],
            ];
        }

        // Display results as a table
        $this->tester->printBenchmarkTable($results, $dbLabel, $comment ?: $layerKey);

        $this->assertTrue(true);
    }
}
