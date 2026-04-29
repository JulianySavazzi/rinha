<?php

declare(strict_types=1);

final class FraudScoreResponse
{
    /**
     *  Possible responses for fraud score;
     *  If >= 60% of neighbors are fraudulent;
     *  5 with fraud label = 100%;
     * @var array|string[]
     */
    private static array $precomputedResponses = [
        0 => '{"approved":true,"fraud_score":0.0}',
        1 => '{"approved":true,"fraud_score":0.2}',
        2 => '{"approved":true,"fraud_score":0.4}',
        3 => '{"approved":false,"fraud_score":0.6}',
        4 => '{"approved":false,"fraud_score":0.8}',
        5 => '{"approved":false,"fraud_score":1.0}',
    ];

    /**
     * can not create instances of this class
     */
    private function __construct(
    ) {}

    public static function makeResponse(array $nearestNeighbors): string
    {
        $fraudCount = 0;

        foreach ($nearestNeighbors as $neighbor) {
            $fraudCount += $neighbor['label'];
        }

        return self::$precomputedResponses[$fraudCount];
    }
}