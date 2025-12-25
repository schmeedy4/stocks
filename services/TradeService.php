<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Trade;
use App\Models\TradeLot;
use App\Models\TradeLotAllocation;
use App\Repositories\TradeLotAllocationRepository;
use App\Repositories\TradeLotRepository;
use App\Repositories\TradeRepository;
use DateTimeImmutable;
use RuntimeException;

final class TradeService
{
    public function __construct(
        private TradeRepository $tradeRepository,
        private TradeLotRepository $lotRepository,
        private TradeLotAllocationRepository $allocationRepository,
    ) {
    }

    public function createBuyTrade(
        int $userId,
        int $instrumentId,
        DateTimeImmutable $tradeDate,
        float $quantity,
        float $pricePerUnit,
        string $tradeCurrency,
        float $fxRateToEur,
        float $feeEur = 0.0,
        ?int $brokerAccountId = null,
        ?float $feeAmount = null,
        ?string $feeCurrency = null,
        ?string $notes = null,
        ?int $actorUserId = null,
    ): int {
        if ($quantity <= 0 || $pricePerUnit <= 0 || $fxRateToEur <= 0) {
            throw new RuntimeException('Quantity, price per unit, and FX rate must be positive.');
        }

        $totalValueEur = $quantity * $pricePerUnit * $fxRateToEur;
        if ($totalValueEur <= 0) {
            throw new RuntimeException('Total value must be positive.');
        }

        if ($feeEur < 0) {
            throw new RuntimeException('Fee cannot be negative.');
        }

        $trade = new Trade(
            id: null,
            userId: $userId,
            instrumentId: $instrumentId,
            tradeType: 'BUY',
            tradeDate: $tradeDate,
            quantity: $quantity,
            pricePerUnit: $pricePerUnit,
            tradeCurrency: $tradeCurrency,
            fxRateToEur: $fxRateToEur,
            totalValueEur: $totalValueEur,
            feeEur: $feeEur,
            brokerAccountId: $brokerAccountId,
            feeAmount: $feeAmount,
            feeCurrency: $feeCurrency,
            notes: $notes,
            createdBy: $actorUserId,
            updatedBy: $actorUserId,
            isVoided: false,
            voidReason: null,
        );

        $tradeId = $this->tradeRepository->create($trade);

        $lot = new TradeLot(
            id: null,
            userId: $userId,
            buyTradeId: $tradeId,
            instrumentId: $instrumentId,
            openedDate: $tradeDate,
            quantityOpened: $quantity,
            quantityRemaining: $quantity,
            costBasisEur: $totalValueEur + $feeEur,
            createdBy: $actorUserId,
            updatedBy: $actorUserId,
        );

        $this->lotRepository->create($lot);

        return $tradeId;
    }

    public function createSellTrade(
        int $userId,
        int $instrumentId,
        DateTimeImmutable $tradeDate,
        float $quantity,
        float $pricePerUnit,
        string $tradeCurrency,
        float $fxRateToEur,
        float $feeEur = 0.0,
        ?int $brokerAccountId = null,
        ?float $feeAmount = null,
        ?string $feeCurrency = null,
        ?string $notes = null,
        ?int $actorUserId = null,
    ): int {
        if ($quantity <= 0 || $pricePerUnit <= 0 || $fxRateToEur <= 0) {
            throw new RuntimeException('Quantity, price per unit, and FX rate must be positive.');
        }

        $totalValueEur = $quantity * $pricePerUnit * $fxRateToEur;
        if ($feeEur < 0) {
            throw new RuntimeException('Fee cannot be negative.');
        }

        $trade = new Trade(
            id: null,
            userId: $userId,
            instrumentId: $instrumentId,
            tradeType: 'SELL',
            tradeDate: $tradeDate,
            quantity: $quantity,
            pricePerUnit: $pricePerUnit,
            tradeCurrency: $tradeCurrency,
            fxRateToEur: $fxRateToEur,
            totalValueEur: $totalValueEur,
            feeEur: $feeEur,
            brokerAccountId: $brokerAccountId,
            feeAmount: $feeAmount,
            feeCurrency: $feeCurrency,
            notes: $notes,
            createdBy: $actorUserId,
            updatedBy: $actorUserId,
            isVoided: false,
            voidReason: null,
        );

        $sellTradeId = $this->tradeRepository->create($trade);
        $this->allocateSellAgainstLots($trade, $sellTradeId, $actorUserId);

        return $sellTradeId;
    }

    private function allocateSellAgainstLots(Trade $sellTrade, int $sellTradeId, ?int $actorUserId): void
    {
        $openLots = $this->lotRepository->findOpenLots($sellTrade->userId, $sellTrade->instrumentId);
        $remaining = $sellTrade->quantity;

        if ($remaining <= 0) {
            throw new RuntimeException('Sell quantity must be positive.');
        }

        $netProceeds = $sellTrade->totalValueEur - $sellTrade->feeEur;

        foreach ($openLots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $consume = min($remaining, $lot->quantityRemaining);
            if ($consume <= 0) {
                continue;
            }

            $proceedsEur = round($netProceeds * ($consume / $sellTrade->quantity), 2);
            $costBasisEur = round($lot->costBasisEur * ($consume / $lot->quantityOpened), 2);
            $realized = $proceedsEur - $costBasisEur;

            $allocation = new TradeLotAllocation(
                id: null,
                userId: $sellTrade->userId,
                sellTradeId: $sellTradeId,
                tradeLotId: $lot->id ?? 0,
                quantityConsumed: $consume,
                proceedsEur: $proceedsEur,
                costBasisEur: $costBasisEur,
                realizedPnlEur: $realized,
                createdBy: $actorUserId,
                updatedBy: $actorUserId,
            );
            $this->allocationRepository->create($allocation);

            $remainingLotQuantity = $lot->quantityRemaining - $consume;
            $this->lotRepository->updateRemainingQuantity($lot->id ?? 0, $remainingLotQuantity);

            $remaining -= $consume;
        }

        if ($remaining > 0) {
            throw new RuntimeException('Not enough open lots to satisfy the SELL quantity.');
        }
    }
}
