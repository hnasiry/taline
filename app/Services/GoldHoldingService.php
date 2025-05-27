<?php

namespace App\Services;

use App\DataTransferObjects\LogTransactionData;
use App\Enums\AssetType;
use App\Enums\Direction;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientGoldException;
use App\Models\User;
use App\ValueObjects\GoldWeight;
use App\ValueObjects\UserId;

class GoldHoldingService
{
    public function __construct(
        private readonly TransactionService $transactionService
    )
    {
    }

    public function ensureSufficientGold(User $user, GoldWeight $requiredWeight): void
    {
        if ($this->getAvailableGold($user)->lessThan($requiredWeight)) {
            throw new InsufficientGoldException();
        }
    }

    public function getGoldBalance(User $user): GoldWeight
    {
        $total = $user->transactions()
            ->where('asset', AssetType::Gold)
            ->selectRaw("SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) AS net")
            ->value('net') ?? 0;

        return GoldWeight::fromMilligrams((int) $total);
    }

    public function getAvailableGold(User $user): GoldWeight
    {
        $balance = $this->getGoldBalance($user);

        $reserved = $user->orders()
            ->where('type', 'sell')
            ->whereIn('status', ['open', 'partial'])
            ->selectRaw('SUM(remaining_weight) AS reserved')
            ->value('reserved') ?? 0;

        $available = max(0, (int) floor($balance->inMilligrams() - $reserved));

        return GoldWeight::fromMilligrams($available);
    }

    public function add(User $user, GoldWeight $amount, TransactionType $type = TransactionType::TRADE, ?string $description = null, $source = null): void
    {
        \DB::transaction(function () use ($user, $amount, $type, $description, $source) {
            $holding = $user->goldHolding()->lockForUpdate()->first();

            $this->transactionService->log(
                new LogTransactionData(
                    userId: new UserId($user->id),
                    amount: $amount->inMilligrams(),
                    direction: Direction::Credit,
                    type: $type,
                    asset: AssetType::Gold,
                    description: $description ?? 'Gold received',
                    source: $source
                )
            );

            // Sync cache
            $holding->weight = $this->getGoldBalance($user)->add($amount);
            $holding->save();
        });
    }

    public function deduct(User $user, GoldWeight $amount, TransactionType $type = TransactionType::TRADE, ?string $description = null, $source = null): void
    {
        \DB::transaction(function () use ($user, $amount, $type, $description, $source) {
            $holding = $user->goldHolding()->lockForUpdate()->first();
            $balance = $this->getGoldBalance($user);

            if ($balance->lessThan($amount)) {
                throw new InsufficientGoldException('Not enough gold balance to deduct.');
            }

            $this->transactionService->log(
                new LogTransactionData(
                    userId: new UserId($user->id),
                    amount: $amount->inMilligrams(),
                    direction: Direction::Debit,
                    type: $type,
                    asset: AssetType::Gold,
                    description: $description ?? 'Gold sent',
                    source: $source
                )
            );

            // Sync cache
            $holding->weight = $balance->subtract($amount);
            $holding->save();
        });
    }

}
