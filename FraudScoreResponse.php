<?php

declare(strict_types=1);

final class FraudScoreResponse
{
    private const THRESHOLD = 0.6; // If >= 60% of neighbors are fraudulent -> block

    /**
     * can not create instances of this class
     */
    private function __construct(
    ) {}

    public static function makeResponse(array $nearestNeighbors): array
    {
        $fraudCount = 0;
        $total = count($nearestNeighbors);

        foreach ($nearestNeighbors as $neighbor) {
            if ($neighbor['label'] === 'fraud') {
                $fraudCount++;
            }
        }

        $fraudScore = $total > 0 ? $fraudCount / $total : 0.0;

        return [
            'approved' => $fraudScore < self::THRESHOLD,
            'fraud_score' => $fraudScore,
        ];
    }
}