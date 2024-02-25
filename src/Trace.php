<?php

declare(strict_types=1);

namespace MarjovanLier\XhprofTrace;

use JsonException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * This class provides methods for enabling and disabling XHProf, a hierarchical profiler for PHP.
 * It also provides methods for generating and displaying a report of the profiling data.
 */
final class Trace
{
    /**
     * Prefixes of classes to be excluded from the report.
     *
     * @var string[]
     */
    private const EXCLUDED_PREFIXES = [
        'Zend_',
        'Composer\\',
        'PHPStan\\',
    ];

    /**
     * Directory where profile data files are stored.
     */
    private static string $profilesDir = '/var/www/html/profiles/';


    /**
     * Sets the directory where profile data files are stored.
     *
     * @param string $path The path to the directory.
     *
     * @noinspection PhpUnused
     */
    public static function setProfilesDir(string $path): void
    {
        self::$profilesDir = $path;
    }


    /**
     * Enables XHProf profiling.
     *
     * @noinspection PhpUnused
     */
    public static function enableXhprof(): void
    {
        xhprof_enable(XHPROF_FLAGS_NO_BUILTINS + XHPROF_FLAGS_MEMORY + XHPROF_FLAGS_CPU);
    }


    /**
     * Disables XHProf profiling and saves the profiling data to a file.
     *
     * @throws JsonException If an error occurs during JSON encoding.
     *
     * @noinspection PhpUnused
     */
    public static function disableXhprof(): void
    {
        $filename = self::$profilesDir . time() . '.application.json';
        file_put_contents($filename, json_encode(xhprof_disable(), JSON_THROW_ON_ERROR));
    }


    /**
     * Generates a report from the profiling data and displays it in the console.
     *
     * @throws JsonException If an error occurs during JSON decoding.
     *
     * @noinspection PhpUnused
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
        $files = glob(self::$profilesDir . '*.json', GLOB_NOSORT);

        return is_array($files) ? $files : [];
    }


    /**
     * Aggregates data from multiple profiling data files.
     *
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
     * Generates a report from a single profiling data file.
     *
     * @return array<string, int>
     *
     * @throws JsonException
     */
    private static function generateReport(string $filename): array
    {
        if (!is_file($filename)) {
            return [];
        }

        $fileContents = file_get_contents($filename);
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
     * Processes raw profiling data for report generation.
     *
     * @param array<string, array{ct: int, wt: int, cpu: int, mu: int, pmu: int, name: string}> $data
     *
     * @return array<string, int>
     */
    private static function processDataForReport(array $data): array
    {
        // This method is a refactored version of the original processing code to address cognitive complexity.
        $filteredData = [];
        foreach ($data as $key => $values) {
            $parts = explode('==>', $key);

            if (count($parts) !== 2) {
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
        asort($combinedRankings);

        return $combinedRankings;
    }


    /**
     * Checks if a class should be excluded from the report based on its prefix.
     *
     * @param string $functionName Name of the function.
     *
     * @return bool Whether the class should be excluded.
     */
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
     * 1. `wt`: Wall time – Total time (in microseconds) taken for both CPU and IO operations.
     * 2. `cpu`: CPU time – Time (in microseconds) the CPU spent executing the function.
     * 3. `mu`: Memory usage – Amount of memory (in bytes) used by the function.
     * 4. `pmu`: Peak memory usage – Peak amount of memory (in bytes) used by the function.
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
        $totalFunctions = count($filteredData);

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
     * @return array<string, int> An associative array where keys are function names and values are their ranks.
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
        $previousMetricValue = null;
        $currentRank = 1;
        $sameMetricValueCount = 0;

        foreach ($data as $item) {
            // Abstracted the calculation of ranks into a separate function
            [
                $currentRank,
                $sameMetricValueCount,
                $previousMetricValue,
            ] = self::calculateRank($item, $metric, $currentRank, $sameMetricValueCount, $previousMetricValue);
            $rankings[$item['name']] = $currentRank;
        }

        return $rankings;
    }


    private static function calculateRank(
        array $item,
        string $metric,
        int $currentRank,
        int $sameMetricValueCount,
        $previousMetricValue
    ): array {
        if ($previousMetricValue === null || $item[$metric] === $previousMetricValue) {
            ++$sameMetricValueCount;
        } else {
            $currentRank += $sameMetricValueCount;
            $sameMetricValueCount = 1;
        }

        $previousMetricValue = $item[$metric];

        return [
            $currentRank,
            $sameMetricValueCount,
            $previousMetricValue,
        ];
    }
}
