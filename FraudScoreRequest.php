<?php

final class FraudScoreRequest
{
    /**
     * @param array $data
     */
    public function __construct(
        private readonly array $data
    ) {}

    /**
     * @param string $payload
     * @return self|null
     */
    public static function validateAndCreate(string $payload): ?self
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

        return new self($data);
    }
}