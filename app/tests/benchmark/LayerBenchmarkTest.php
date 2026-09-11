<?php

namespace app\tests\benchmark;

use app\inc\Cache;
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
        'f_table_schema',
        'f_table_name',
        'f_geometry_column',
        'coord_dimension',
        'srid',
        'type',
        '_key_',
        'f_table_abstract',
        'f_table_title',
        'tweet',
        'editable',
        'created',
        'lastmodified',
        'authentication',
        'fieldconf',
        'meta_url',
        'layergroup',
        'def',
        'class',
        'wmssource',
        'baselayer',
        'sort_id',
        'tilecache',
        'data',
        'not_querable',
        'single_tile',
        'cartomobile',
        'filter',
        'bitmapsource',
        'privileges',
        'enablesqlfilter',
        'triggertable',
        'classwizard',
        'extra',
        'skipconflict',
        'roles',
        'elasticsearch',
        'uuid',
        'tags',
        'meta',
        'wmsclientepsgs',
        'featureid',
        'note',
        'legend_url',
        'enableows',
        'class_cache',
        'qml'
    ];

    /**
     * Number of iterations for all benchmarks
     */
    protected int $iterations = 5;

    protected function _before(): void
    {
        Cache::setInstance();
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

        // Save results if BENCHMARK_SAVE environment variable is set
        $saveMode = getenv('BENCHMARK_SAVE');
        if ($saveMode) {
            $this->tester->saveResults($results, $saveMode);
        }

        // Compare results if BENCHMARK_COMPARE environment variable is set
        $compareMode = getenv('BENCHMARK_COMPARE');
        if ($compareMode) {
            $this->tester->compareResults($results, $compareMode);
        }

        $this->assertTrue(true);
    }

    /**
     * Define subusers and privilege levels to benchmark with their database
     * 
     * Format: array of [$database, $subuser, $privilege, $comment]
     */
    public function setPrivilegesOnAllProvider(): array
    {
        return [
            // EDIT BELOW: Add your subusers with database here
            // Format: [database_name, subuser_name, privilege_level, comment]
            ['e2e', 'gio.borella@gmail.com', 'none', 'Setting all privileges to none'],
        ];
    }

    /**
     * Benchmark Layer::setPrivilegesOnAll() with data provider
     * 
     * @dataProvider setPrivilegesOnAllProvider
     */
    public function testSetPrivilegesOnAllWithLayers($database, $subuser, $privilege, $comment): void
    {
        $dbLabel = $database ?? 'default';
        
        $commentLabel = $comment ? " ({$comment})" : '';
        $this->tester->wantTo("Benchmark Layer::setPrivilegesOnAll() for subuser: $subuser, privilege: $privilege on database: $dbLabel{$commentLabel}");

        // Create layer with specific database connection if provided
        if ($database) {
            $connection = new Connection(database: $database);
            $layer = new Layer(connection: $connection);
        } else {
            $layer = $this->layer;
        }

        // Verify the subuser exists (skip test if not found)
        try {
            $layer->setPrivilegesOnAll($subuser, $privilege);
        } catch (\Exception $e) {
            $this->markTestSkipped("Subuser '$subuser' not found in database '$dbLabel' or error occurred: " . $e->getMessage());
        }

        // Collect benchmark results
        $results = [];
        $benchmarkResult = $this->tester->benchmarkFunction(
            function () use ($layer, $subuser, $privilege) {
                $layer->setPrivilegesOnAll($subuser, $privilege);
            },
            $this->iterations
        );

        $results[] = [
            'database' => $dbLabel,
            'subuser' => $subuser,
            'privilege' => $privilege,
            'operation' => 'setPrivilegesOnAll',
            'iterations' => $benchmarkResult['iterations'],
            'total_ms' => $benchmarkResult['totalMs'],
            'avg_ms' => $benchmarkResult['averageMs'],
            'fastest' => $benchmarkResult['fastest'],
            'slowest' => $benchmarkResult['slowest'],
            'p95' => $benchmarkResult['p95'],
            'first' => $benchmarkResult['first'],
        ];

        // Display results as a table
        $this->tester->printBenchmarkTable($results, $dbLabel, "setPrivilegesOnAll: {$comment}");

        // Save results if BENCHMARK_SAVE environment variable is set
        $saveMode = getenv('BENCHMARK_SAVE');
        if ($saveMode) {
            $this->tester->saveResults($results, $saveMode);
        }

        // Compare results if BENCHMARK_COMPARE environment variable is set
        $compareMode = getenv('BENCHMARK_COMPARE');
        if ($compareMode) {
            $this->tester->compareResults($results, $compareMode);
        }

        $this->assertTrue(true);
    }
}
