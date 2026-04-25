<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

final class FraudScoreRequest
{
    /**
     * can not create instances of this class
     */
    public function __construct(
    ) {}

    /**
     * Check payload structure for detect fraud score request
     * @param string $payload
     * @return array|null
     */
    public static function validateAndCreate(string $payload): ?array
    {
        $data = json_decode($payload, true);

        if (!isset($data['id'], $data['transaction'], $data['customer'], $data['merchant'], $data['terminal'])) {
            return null;
        }

        if (!is_string($data['id'])) {
            return null;
        }

        if (!is_numeric($data['transaction']['amount'])
            || !is_int($data['transaction']['installments'])
            || !is_string($data['transaction']['requested_at'])) {
            return null;
        }

        if (!is_numeric($data['customer']['avg_amount'])
            || !is_int($data['customer']['tx_count_24h'])
            || !is_array($data['customer']['known_merchants'])) {
            return null;
        }

        if (!is_string($data['merchant']['id'])
            || !is_string($data['merchant']['mcc'])
            || !is_numeric($data['merchant']['avg_amount'])) {
            return null;
        }

        if (!is_bool($data['terminal']['is_online'])
            || !is_bool($data['terminal']['card_present'])
            || !is_numeric($data['terminal']['km_from_home'])) {
            return null;
        }

        if (isset($data['last_transaction'])
            && (!is_string($data['last_transaction']['timestamp'])
            || !is_numeric($data['last_transaction']['km_from_current']))) {
            return null;
        }

        return (array)$data;
    }

    /**
     * Normalize data and return vector for vector search,
     * index from 0 to 13,
     * index 5 and 6, when null may be -1
     * @param array $data
     * @return array
     */
    public static function toVector(array $data): array
    {
        /**
         * constant limits for normalization:
         * "max_amount": 10000,
         * "max_installments": 12,
         * "amount_vs_avg_ratio": 10,
         * "max_minutes": 1440,
         * "max_km": 1000,
         * "max_tx_count_24h": 20,
         * "max_merchant_avg_amount": 10000
         */
        $maxAmount = 10000;
        $maxInstallments = 12;
        $amountVsAvgRatio = 10;
        $maxMinutes = 1440;
        $maxKm = 1000;
        $maxTxCount24h = 20;
        $maxMerchantAvgAmount = 10000;

        // hours from 0 to 23
        $requestedHour = (int) gmdate('H', strtotime($data['transaction']['requested_at']));
        // weekdays from 0 monday to 6 sunday
        $requestedWeekday = (int) gmdate('N', strtotime($data['transaction']['requested_at'])) - 1;
        // for index 5 and 6, when null may be -1
        $lastTransaction = $data['last_transaction'] ?? null;

        /**
         * for mcc_risk.json:
         * "5411": 0.15,
         * "5812": 0.30,
         * "5912": 0.20,
         * "5944": 0.45,
         * "7801": 0.80,
         * "7802": 0.75,
         * "7995": 0.85,
         * "4511": 0.35,
         * "5311": 0.25,
         * "5999": 0.50
         */
        $mccRiskMap = [
            "5411" => 0.15,
            "5812" => 0.30,
            "5912" => 0.20,
            "5944" => 0.45,
            "7801" => 0.80,
            "7802" => 0.75,
            "7995" => 0.85,
            "4511" => 0.35,
            "5311" => 0.25,
            "5999" => 0.50
        ];

        $amount = helpers::clamp($data['transaction']['amount']/$maxAmount);
        $installments = helpers::clamp($data['transaction']['installments']/$maxInstallments);
        $amountVsAvg = helpers::clamp(
            ($data['transaction']['amount']/$data['customer']['avg_amount'])
            /$amountVsAvgRatio);
        $hourOfDay = $requestedHour/23;
        $dayOfWeek = $requestedWeekday/6;
        $minutesSinceLastTx = $lastTransaction
            ? helpers::clamp(
                ((strtotime($data['transaction']['requested_at']) - strtotime($lastTransaction['timestamp']))/60)
                /$maxMinutes)
            : -1;
        $kmFromLastTx = $lastTransaction ? helpers::clamp($lastTransaction['km_from_current']/$maxKm) : -1;
        $kmFromHome = helpers::clamp($data['terminal']['km_from_home']/$maxKm);
        $txCount24h = helpers::clamp($data['customer']['tx_count_24h']/$maxTxCount24h);
        $isOnline = $data['terminal']['is_online'] ? 1 : 0;
        $cardPresent = $data['terminal']['card_present'] ? 1 : 0;
        $unknownMerchant = in_array($data['merchant']['id'], $data['customer']['known_merchants'], true) ? 0 : 1;
        $mccRisk = $mccRiskMap[$data['merchant']['mcc']] ?? 0.5;
        $merchantAvgAmount = helpers::clamp($data['merchant']['avg_amount']/$maxMerchantAvgAmount);

        return [
            $amount,
            $installments,
            $amountVsAvg,
            $hourOfDay,
            $dayOfWeek,
            $minutesSinceLastTx,
            $kmFromLastTx,
            $kmFromHome,
            $txCount24h,
            $isOnline,
            $cardPresent,
            $unknownMerchant,
            $mccRisk,
            $merchantAvgAmount
        ];
    }
}