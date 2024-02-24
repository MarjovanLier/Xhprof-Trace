<?php

declare(strict_types=1);

namespace MarjovanLier\XhprofTrace;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @phan-file-suppress PhanUndeclaredConstant
 * @phan-file-suppress PhanUndeclaredFunction
 */
final class Trace
{
    /**
     * @var string
     */
    private const  PROFILES_DIR = '/var/www/html/profiles/';

    /**
     * @var string[]
     */
    private const  EXCLUDED_PREFIXES = [
        'Zend_',
        'Composer\\',
        'PHPStan\\',
    ];


    public static function enableXhprof(): void
    {
        xhprof_enable(\XHPROF_FLAGS_NO_BUILTINS + \XHPROF_FLAGS_MEMORY + \XHPROF_FLAGS_CPU);
    }


    public static function disableXhprof(): void
    {
        $filename = self::PROFILES_DIR . time() . '.application.json';
        file_put_contents($filename, json_encode(xhprof_disable(), JSON_THROW_ON_ERROR));
    }


    /**
     * @throws JsonException
     */
    public static function displayReportCLI(): void
    {
        $files = self::getAllProfileFiles();

        $aggregatedData = self::getAggregatedDataFromFiles($files);

        $consoleOutput = new ConsoleOutput();
        $table = new Table($consoleOutput);
        $table->setHeaders(['Function', 'Score', 'Occurrences']);

        foreach ($aggregatedData as $name => $data) {
            $table->addRow([$name, $data['score'], $data['count']]);
        }

        $table->render();
    }


    /**
     * Retrieves all profile JSON file paths from the profiles directory.
     *
     * @return array<int, string> An array of absolute file paths.
     *
     * @psalm-return list<string>
     */
    private static function getAllProfileFiles(): array
    {
        $files = glob(self::PROFILES_DIR . '*.json', GLOB_NOSORT);

        return is_array($files) ? $files : [];
    }


    /**
     * @param array<int, string> $files
     *
     * @return array<string, array{score: int, count: int}>
     *
     * @throws JsonException
     *
     * @psalm-return array<string, array{count: int, score: int}>
     */
    private static function getAggregatedDataFromFiles(array $files): array
    {
        $aggregatedScores = [];
        $functionOccurrences = [];

        foreach ($files as $file) {
            foreach (self::generateReport($file) as $name => $score) {
                $aggregatedScores[$name] = (($aggregatedScores[$name] ?? 0) + $score);
                $functionOccurrences[$name] = (($functionOccurrences[$name] ?? 0) + 1);
            }
        }

        $results = [];
        foreach ($aggregatedScores as $name => $score) {
            $results[$name] = [
                'count' => ($functionOccurrences[$name] ?? 0),
                'score' => (int) round($score / ($functionOccurrences[$name] ?? 1)),
            ];
        }

        return $results;
    }


    /**
     * @return array<string, int>
     *
     * @throws JsonException
     */
    private static function generateReport(string $filename): array
    {
        if (!\is_file($filename)) {
            return [];
        }

        $fileContents = \file_get_contents($filename);
        if ($fileContents === false) {
            return [];
        }

        /**
         * @var array<string, array{ct: int, wt: int, cpu: int, mu: int, pmu: int, name: string}> $data
         */
        $data = json_decode($fileContents, true, 512, JSON_THROW_ON_ERROR);

        return self::processDataForReport($data);
    }


    /**
     * @param array<string, array{ct: int, wt: int, cpu: int, mu: int, pmu: int, name: string}> $data
     *
     * @return array<string, int>
     */
    private static function processDataForReport(array $data): array
    {
        // This method is a refactored version of the original processing code to address cognitive complexity.
        $filteredData = [];
        foreach ($data as $key => $values) {
            $parts = \explode('==>', $key);

            if (\count($parts) !== 2) {
                continue;
            }

            [
                ,
                $callee,
            ] = $parts;

            if (self::isExcludedClass($callee)) {
                continue;
            }

            $values['name'] = $callee;
            $filteredData[] = $values;
        }

        $combinedRankings = self::computeCombinedRankings($filteredData);
        \asort($combinedRankings);

        return $combinedRankings;
    }


    private static function isExcludedClass(string $functionName): bool
    {
        foreach (self::EXCLUDED_PREFIXES as $excludedPrefix) {
            if (str_starts_with($functionName, $excludedPrefix)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Computes a combined ranking for functions based on various XHProf metrics.
     *
     * The ranking is computed by first reversing the rank for each metric
     * (except for 'ct') such that functions that perform better get higher ranks.
     * Then, the combined rank is computed by summing the reversed ranks for
     * each metric and multiplying by the call count (`ct`), emphasizing the
     * importance of frequently called functions.
     *
     * Metrics used for ranking:
     * 1. `wt`: Wall time - Total time (in microseconds) taken for both CPU and IO operations.
     * 2. `cpu`: CPU time - Time (in microseconds) the CPU spent executing the function.
     * 3. `mu`: Memory usage - Amount of memory (in bytes) used by the function.
     * 4. `pmu`: Peak memory usage - Peak amount of memory (in bytes) used by the function.
     *
     * The call count `ct` is used as a multiplier to adjust the ranking based on the frequency
     * of function calls.
     *
     * @param array<int, array{ct: int, wt: int, cpu: int, mu: int, pmu: int, name: string}> $filteredData
     *
     * @return array<string, int>
     */
    private static function computeCombinedRankings(array $filteredData): array
    {
        $rankingsByWt = self::rankByMetric($filteredData, 'wt');
        $rankingsByCPU = self::rankByMetric($filteredData, 'cpu');
        $rankingsByMemory = self::rankByMetric($filteredData, 'mu');
        $rankingsByPeakMemory = self::rankByMetric($filteredData, 'pmu');

        $combinedRankings = [];
        $totalFunctions = \count($filteredData);

        foreach ($filteredData as $item) {
            $name = $item['name'];
            // Subtract each rank from totalFunctions + 1 to reverse the rank
            $reversedRank = (
                ($totalFunctions + 1 - ($rankingsByWt[$name] ?? 0)) +
                ($totalFunctions + 1 - ($rankingsByCPU[$name] ?? 0)) +
                ($totalFunctions + 1 - ($rankingsByMemory[$name] ?? 0)) +
                ($totalFunctions + 1 - ($rankingsByPeakMemory[$name] ?? 0))
            );

            // Multiply the reversed rank by the call count
            $combinedRankings[$name] = ($reversedRank * $item['ct']);
        }

        return $combinedRankings;
    }


    /**
     * Ranks data by the specified metric.
     *
     * Given a multidimensional array of performance metrics, this function sorts
     * and ranks the data based on the provided metric. The resulting array maps
     * function names to their respective ranks.
     *
     * @param array<int, array{ct: int, wt: int, cpu: int, mu: int, pmu: int, name: string}> $data
     *
     * @return array<string, int> Associative array where keys are function names and values are their ranks.
     *
     * @psalm-return array<string, int<1, max>>
     */
    private static function rankByMetric(array $data, string $metric): array
    {
        $comparisonFunction = static fn(
            array $firstItem,
            array $secondItem
        ): int => ((int) $secondItem[$metric] - (int) $firstItem[$metric]);

        usort($data, $comparisonFunction);

        $rankings = [];
        $previousValue = null;
        $currentRank = 1;
        $tiedCount = 0;

        foreach ($data as $item) {
            if ($previousValue === null) {
                $rankings[$item['name']] = $currentRank;
                $previousValue = $item[$metric];

                continue;
            }

            if ($item[$metric] === $previousValue) {
                $rankings[$item['name']] = $currentRank;
                ++$tiedCount;
                $previousValue = $item[$metric];

                continue;
            }

            $currentRank += ($tiedCount + 1);
            $tiedCount = 0;
            $rankings[$item['name']] = $currentRank;
            $previousValue = $item[$metric];
        }

        return $rankings;
    }
}
