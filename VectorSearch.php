<?php

declare(strict_types=1);

final class VectorSearch
{
    private static array $dataset = [];
    private static int $datasetSize = 0;

    /**
     * can not create instances of this class
     */
    private function __construct(
    ) {}

    /**
     * load data and sort by amount (index 0)
     * @param string $filepath
     * @return void
     */
    public static function loadDataset(string $filepath): void
    {
        $json = gzdecode(file_get_contents($filepath));
        self::$dataset = json_decode($json, true);
        self::$datasetSize = count(self::$dataset);
        if (self::$dataset === null) {
            echo "⚠️ ERROR ON JSON: " . json_last_error_msg() . "\n";
            echo "Try ready as JSON Lines...\n";

            self::$dataset = [];
            $lines = explode("\n", trim($json));

            foreach ($lines as $line) {
                if ($line !== '') {
                    self::$dataset[] = json_decode($line, true);
                }
            }
            self::$datasetSize = count(self::$dataset);
        }

        echo "✅ Dataset loaded! Total memory records: " . count(self::$dataset) . "\n";
        usort(self::$dataset, fn($a, $b) => $a['vector'][0] <=> $b['vector'][0]);
    }

    /**
     * simplified ANN (Approximate Nearest Neighbor) search
     * @param array $targetVector
     * @param int $k
     * @param int $windowRadius
     * @return array
     */
    public static function search(array $targetVector, int $k = 5, int $windowRadius = 2000): array
    {
        $dataset = self::$dataset;
        $size = self::$datasetSize;

        $targetAmount = $targetVector[0];
        $nearestIndex = self::binarySearchAmount($targetAmount); // center index

        $start = max(0, $nearestIndex - $windowRadius);
        $end = min($size - 1, $nearestIndex + $windowRadius);

        $bestNeighbors = [];
        $worstDist = -1.0;
        $worstIndex = 0;
        $count = 0;

        for ($i = $start; $i <= $end; $i++) {
            $vector = $dataset[$i]['vector'];

            $distance = (($targetVector[0] - $vector[0]) * ($targetVector[0] - $vector[0]))
                + (($targetVector[1] - $vector[1]) * ($targetVector[1] - $vector[1]))
                + (($targetVector[2] - $vector[2]) * ($targetVector[2] - $vector[2]))
                + (($targetVector[3] - $vector[3]) * ($targetVector[3] - $vector[3]))
                + (($targetVector[4] - $vector[4]) * ($targetVector[4] - $vector[4]))
                + (($targetVector[5] - $vector[5]) * ($targetVector[5] - $vector[5]))
                + (($targetVector[6] - $vector[6]) * ($targetVector[6] - $vector[6]))
                + (($targetVector[7] - $vector[7]) * ($targetVector[7] - $vector[7]))
                + (($targetVector[8] - $vector[8]) * ($targetVector[8] - $vector[8]))
                + (($targetVector[9] - $vector[9]) * ($targetVector[9] - $vector[9]))
                + (($targetVector[10] - $vector[10]) * ($targetVector[10] - $vector[10]))
                + (($targetVector[11] - $vector[11]) * ($targetVector[11] - $vector[11]))
                + (($targetVector[12] - $vector[12]) * ($targetVector[12] - $vector[12]))
                + (($targetVector[13] - $vector[13]) * ($targetVector[13] - $vector[13]));

            if ($count < $k) {
                $bestNeighbors[$count] = ['dist' => $distance, 'data' => $dataset[$i]];

                if ($distance > $worstDist) {
                    // update worst neighbor - badder than current worst
                    $worstDist = $distance;
                    $worstIndex = $count;
                }

                $count++;
                continue;
            }

            // Max-Heap - dont use usort
            if ($distance < $worstDist) {
                $bestNeighbors[$worstIndex] = ['dist' => $distance, 'data' => $dataset[$i]];

                $worstDist = $bestNeighbors[0]['dist'];
                $worstIndex = 0;

                for ($w = 1; $w < $k; $w++) {
                    if ($bestNeighbors[$w]['dist'] > $worstDist) {
                        $worstDist = $bestNeighbors[$w]['dist'];
                        $worstIndex = $w;
                    }
                }
            }
        }

        return array_column($bestNeighbors, 'data');
    }

    /**
     * Binary search O(log N) to find the index with the closest amount
     * @param float $targetAmount
     * @return int
     */
    private static function binarySearchAmount(float $targetAmount): int
    {
        $low = 0; // starts array search
        $high = count(self::$dataset) - 1; // finish array search

        $closestIndex = 0; // finded index
        $smallestDistance = PHP_FLOAT_MAX;

        while ($low <= $high) {
            $mid = (int) (($low + $high) / 2);
            $currentAmount = self::$dataset[$mid]['vector'][0];

            // update finded closest index
            $diff = abs($currentAmount - $targetAmount);
            if ($diff < $smallestDistance) {
                $smallestDistance = $diff;
                $closestIndex = $mid;
            }

            // match found
            if ($currentAmount === $targetAmount) {
                return $mid;
            }

            // adjust search range
            if ($currentAmount < $targetAmount) {
                $low = $mid + 1;
                continue;
            }

            $high = $mid - 1;
        }
        return $closestIndex;
    }
}