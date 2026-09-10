<?php

namespace app\tests\benchmark;

use Codeception\Test\Unit;

class ExampleBenchmarkTest extends Unit
{
    /**
     * @var \BenchmarkTester
     */
    protected $tester;

    /**
     * Example: Benchmark a simple PHP function
     */
    public function testArrayOperations(): void
    {
        $this->tester->wantTo('Benchmark array operations');

        // Benchmark array_merge
        $results = $this->tester->benchmarkFunction(function () {
            $arr1 = range(1, 100);
            $arr2 = range(101, 200);
            array_merge($arr1, $arr2);
        }, 1000);

        $this->tester->printBenchmarkResults('array_merge (1000 iterations)', $results);
        $this->assertTrue(true);
    }

    /**
     * Example: Benchmark array_map vs foreach
     */
    public function testArrayMap(): void
    {
        $this->tester->wantTo('Benchmark array_map vs foreach');

        $data = range(1, 100);

        // Benchmark array_map
        $resultsMap = $this->tester->benchmarkFunction(function () use ($data) {
            array_map(function ($x) {
                return $x * 2;
            }, $data);
        }, 1000);

        $this->tester->printBenchmarkResults('array_map (1000 iterations)', $resultsMap);

        // Benchmark foreach
        $resultsForeach = $this->tester->benchmarkFunction(function () use ($data) {
            $result = [];
            foreach ($data as $x) {
                $result[] = $x * 2;
            }
        }, 1000);

        $this->tester->printBenchmarkResults('foreach (1000 iterations)', $resultsForeach);
        $this->assertTrue(true);
    }

    /**
     * Example: Benchmark string operations
     */
    public function testStringOperations(): void
    {
        $this->tester->wantTo('Benchmark string operations');

        $results = $this->tester->benchmarkFunction(function () {
            $str = str_repeat('test', 100);
            strlen($str);
            substr($str, 0, 10);
            strpos($str, 'test');
        }, 1000);

        $this->tester->printBenchmarkResults('string operations (1000 iterations)', $results);
        $this->assertTrue(true);
    }
}
