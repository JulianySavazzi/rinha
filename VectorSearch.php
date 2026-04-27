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

        // cache target values (faster than array access)
        $t0 = $targetVector[0];
        $t1 = $targetVector[1];
        $t2 = $targetVector[2];
        $t3 = $targetVector[3];
        $t4 = $targetVector[4];
        $t5 = $targetVector[5];
        $t6 = $targetVector[6];
        $t7 = $targetVector[7];
        $t8 = $targetVector[8];
        $t9 = $targetVector[9];
        $t10 = $targetVector[10];
        $t11 = $targetVector[11];
        $t12 = $targetVector[12];
        $t13 = $targetVector[13];

        $bestNeighbors = [];
        $worstDist = -1.0;
        $worstIndex = 0;
        $count = 0;

        for ($i = $start; $i <= $end; $i++) {
            $vector = $dataset[$i]['vector'];

            /**
             * separated distance in blocks for early exit;
             * block 1 for Euclidiane Distance - high variance index
             */
            $distance = (($t2 - $vector[2]) * ($t2 - $vector[2]))
                + (($t5 - $vector[5]) * ($t5 - $vector[5]))
                + (($t6 - $vector[6]) * ($t6 - $vector[6]))
                + (($t7 - $vector[7]) * ($t7 - $vector[7]))
                + (($t12 - $vector[12]) * ($t12 - $vector[12]));

            if ($count >= $k && $distance >= $worstDist) {
                continue;
            }

            /**
             * block 2 for Euclidiane Distance - medium variance index
             */
            $distance += (($t8 - $vector[8]) * ($t8 - $vector[8]))
                + (($t11 - $vector[11]) * ($t11 - $vector[11]))
                + (($t13 - $vector[13]) * ($t13 - $vector[13]))
                + (($t3 - $vector[3]) * ($t3 - $vector[3]))
                + (($t4 - $vector[4]) * ($t4 - $vector[4]));

            if ($count >= $k && $distance >= $worstDist) {
                continue;
            }

            /**
             * block 3 for Euclidiane Distance - low variance index, amount last
             */
            $distance += (($t9 - $vector[9]) * ($t9 - $vector[9]))
                + (($t10 - $vector[10]) * ($t10 - $vector[10]))
                + (($t1 - $vector[1]) * ($t1 - $vector[1]))
                + (($t0 - $vector[0]) * ($t0 - $vector[0]));

            // fill initial neighbors
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

            // Max-Heap - dont use usort and replace worst if better
            if ($distance < $worstDist) {
                $bestNeighbors[$worstIndex] = ['dist' => $distance, 'data' => $dataset[$i]];

                // recompute worst neighbor
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