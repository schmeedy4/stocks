<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Trade;
use DateTimeImmutable;
use PDO;

final class TradeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(Trade $trade): int
    {
        $sql = 'INSERT INTO trade (
            user_id, broker_account_id, instrument_id,
            trade_type, trade_date,
            quantity, price_per_unit, trade_currency,
            fee_amount, fee_currency,
            fx_rate_to_eur, total_value_eur, fee_eur,
            notes, is_voided, void_reason,
            created_by, updated_by
        ) VALUES (
            :user_id, :broker_account_id, :instrument_id,
            :trade_type, :trade_date,
            :quantity, :price_per_unit, :trade_currency,
            :fee_amount, :fee_currency,
            :fx_rate_to_eur, :total_value_eur, :fee_eur,
            :notes, :is_voided, :void_reason,
            :created_by, :updated_by
        )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $trade->userId,
            'broker_account_id' => $trade->brokerAccountId,
            'instrument_id' => $trade->instrumentId,
            'trade_type' => $trade->tradeType,
            'trade_date' => $trade->tradeDate->format('Y-m-d'),
            'quantity' => $trade->quantity,
            'price_per_unit' => $trade->pricePerUnit,
            'trade_currency' => $trade->tradeCurrency,
            'fee_amount' => $trade->feeAmount,
            'fee_currency' => $trade->feeCurrency,
            'fx_rate_to_eur' => $trade->fxRateToEur,
            'total_value_eur' => $trade->totalValueEur,
            'fee_eur' => $trade->feeEur,
            'notes' => $trade->notes,
            'is_voided' => $trade->isVoided ? 1 : 0,
            'void_reason' => $trade->voidReason,
            'created_by' => $trade->createdBy,
            'updated_by' => $trade->updatedBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
