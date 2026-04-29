<?php

declare(strict_types=1);

final class VectorSearch
{
    private static \SplFixedArray $flatVectors;
    private static \SplFixedArray $flatLabels;
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
        $tempDataset = json_decode($json, true);
        self::$datasetSize = count($tempDataset);

        usort($tempDataset, fn($a, $b) => $a['vector'][0] <=> $b['vector'][0]);

        self::$flatVectors = new \SplFixedArray(self::$datasetSize * 14);
        self::$flatLabels = new \SplFixedArray(self::$datasetSize);
        foreach ($tempDataset as $i => $item) {
            $baseIdx = $i * 14;
            self::$flatVectors[$baseIdx + 0] = $item['vector'][0];
            self::$flatVectors[$baseIdx + 1] = $item['vector'][1];
            self::$flatVectors[$baseIdx + 2] = $item['vector'][2];
            self::$flatVectors[$baseIdx + 3] = $item['vector'][3];
            self::$flatVectors[$baseIdx + 4] = $item['vector'][4];
            self::$flatVectors[$baseIdx + 5] = $item['vector'][5];
            self::$flatVectors[$baseIdx + 6] = $item['vector'][6];
            self::$flatVectors[$baseIdx + 7] = $item['vector'][7];
            self::$flatVectors[$baseIdx + 8] = $item['vector'][8];
            self::$flatVectors[$baseIdx + 9] = $item['vector'][9];
            self::$flatVectors[$baseIdx + 10] = $item['vector'][10];
            self::$flatVectors[$baseIdx + 11] = $item['vector'][11];
            self::$flatVectors[$baseIdx + 12] = $item['vector'][12];
            self::$flatVectors[$baseIdx + 13] = $item['vector'][13];
            // Fraud = 1, Legit = 0
            self::$flatLabels[$i] = $item['label'] === 'fraud' ? 1 : 0;
        }
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
        $size = self::$datasetSize;
        $targetAmount = $targetVector[0];
        $nearestIndex = self::binarySearchAmount($targetAmount); // center index

        $start = max(0, $nearestIndex - $windowRadius);
        $end = min($size - 1, $nearestIndex + $windowRadius);

        // cache target values (faster than array access)
        $t0 = $targetAmount;
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

        $flatVectors = self::$flatVectors; // local cache
        $flatLabels = self::$flatLabels;   // local cache

        $bestNeighbors = [];
        $worstDist = -1.0;
        $worstIndex = 0;
        $count = 0;

        for ($i = $start; $i <= $end; $i++) {
            $baseIdx = $i * 14;

            /**
             * separated distance in blocks for early exit;
             * block 1 for Euclidiane Distance - high variance index
             */
            $distance = (($t2 - $flatVectors[$baseIdx + 2]) * ($t2 - $flatVectors[$baseIdx + 2]))
                + (($t5 - $flatVectors[$baseIdx + 5]) * ($t5 - $flatVectors[$baseIdx + 5]))
                + (($t6 - $flatVectors[$baseIdx + 6]) * ($t6 - $flatVectors[$baseIdx + 6]))
                + (($t7 - $flatVectors[$baseIdx + 7]) * ($t7 - $flatVectors[$baseIdx + 7]))
                + (($t12 - $flatVectors[$baseIdx + 12]) * ($t12 - $flatVectors[$baseIdx + 12]));


            if ($count >= $k && $distance >= $worstDist) {
                continue;
            }

            /**
             * block 2 for Euclidiane Distance - medium variance index
             */
            $distance += (($t8 - $flatVectors[$baseIdx + 8]) * ($t8 - $flatVectors[$baseIdx + 8]))
                + (($t11 - $flatVectors[$baseIdx + 11]) * ($t11 - $flatVectors[$baseIdx + 11]))
                + (($t13 - $flatVectors[$baseIdx + 13]) * ($t13 - $flatVectors[$baseIdx + 13]))
                + (($t3 - $flatVectors[$baseIdx + 3]) * ($t3 - $flatVectors[$baseIdx + 3]))
                + (($t4 - $flatVectors[$baseIdx + 4]) * ($t4 - $flatVectors[$baseIdx + 4]));

            if ($count >= $k && $distance >= $worstDist) {
                continue;
            }

            /**
             * block 3 for Euclidiane Distance - low variance index, amount last
             */
            $distance += (($t9 - $flatVectors[$baseIdx + 9]) * ($t9 - $flatVectors[$baseIdx + 9]))
                + (($t10 - $flatVectors[$baseIdx + 10]) * ($t10 - $flatVectors[$baseIdx + 10]))
                + (($t1 - $flatVectors[$baseIdx + 1]) * ($t1 - $flatVectors[$baseIdx + 1]))
                + (($t0 - $flatVectors[$baseIdx + 0]) * ($t0 - $flatVectors[$baseIdx + 0]));

            // fill initial neighbors
            if ($count < $k) {
                $bestNeighbors[$count] = ['dist' => $distance, 'label' => $flatLabels[$i]];

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
                $bestNeighbors[$worstIndex] = ['dist' => $distance, 'label' => $flatLabels[$i]];

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

        return $bestNeighbors;
    }

    /**
     * Binary search O(log N) to find the index with the closest amount
     * @param float $targetAmount
     * @return int
     */
    private static function binarySearchAmount(float $targetAmount): int
    {
        $low = 0; // starts array search
        $high = self::$datasetSize - 1; // finish array search

        $closestIndex = 0; // finded index
        $smallestDistance = PHP_FLOAT_MAX;
        $flatVectors = self::$flatVectors;

        while ($low <= $high) {
            $mid = (int) (($low + $high) / 2);
            $currentAmount = $flatVectors[$mid * 14];

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