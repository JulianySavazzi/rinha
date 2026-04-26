<?php

declare(strict_types=1);

final class VectorSearch
{
    private static array $dataset = [];

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
        $targetAmount = $targetVector[0];
        $nearestIndex = self::binarySearchAmount($targetAmount); // center index

        $dataset = self::$dataset;
        $size = count($dataset);

        $start = max(0, $nearestIndex - $windowRadius);
        $end = min($size - 1, $nearestIndex + $windowRadius);

        $bestNeighbors = [];
        for ($i = $start; $i <= $end; $i++) {
            $candidate = $dataset[$i];
            $vector = $candidate['vector'];

            $distance = 0.0;
            for ($j = 0; $j < 14; $j++) {
                $diff = $targetVector[$j] - $vector[$j];
                $distance += $diff * $diff;
            }

            if (count($bestNeighbors) < $k) {
                $bestNeighbors[] = ['dist' => $distance, 'data' => $candidate];
                usort($bestNeighbors, fn($a, $b) => $a['dist'] <=> $b['dist']);
                continue;
            }

            if ($distance < $bestNeighbors[$k - 1]['dist']) {
                $bestNeighbors[$k - 1] = ['dist' => $distance, 'data' => $candidate];
                usort($bestNeighbors, fn($a, $b) => $a['dist'] <=> $b['dist']);
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