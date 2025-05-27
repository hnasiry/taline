<?php

namespace App\Services;

use App\DataTransferObjects\LogTransactionData;
use App\Enums\AssetType;
use App\Enums\Direction;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Models\User;
use App\ValueObjects\RialAmount;
use App\ValueObjects\UserId;

class WalletService
{
    public function __construct(
        private readonly TransactionService $transactionService
    )
    {
    }

    public function ensureSufficientFunds(User $user, RialAmount $totalRequired): void
    {
        if ($this->getAvailableBalance($user)->lessThan($totalRequired)) {
            throw new InsufficientWalletBalanceException();
        }
    }

    public function getBalance(User $user): RialAmount
    {
        $total = $user->transactions()
            ->where('asset', AssetType::Rial)
            ->selectRaw("SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) AS net")
            ->value('net') ?? 0;

        return new RialAmount((int) $total);
    }

    public function getAvailableBalance(User $user): RialAmount
    {
        $balance = $this->getBalance($user);

        $reserved = $user->orders()
            ->where('type', 'buy')
            ->whereIn('status', ['open', 'partial'])
            ->selectRaw('SUM((remaining_weight / total_weight) * total_price) AS reserved')
            ->value('reserved') ?? 0;

        $available = max(0, (int) floor($balance->getValue() - $reserved));

        return new RialAmount($available);
    }

    public function charge(User $user, RialAmount $amount, TransactionType $type = TransactionType::TRADE, ?string $description = null, $source = null): void
    {
        \DB::transaction(function () use ($user, $amount, $type, $description, $source) {
            $wallet = $user->wallet()->lockForUpdate()->first();

            $balance = $this->getBalance($user);

            if ($balance->lessThan($amount)) {
                throw new InsufficientWalletBalanceException('Not enough wallet balance to charge.');
            }

            $this->transactionService->log(
                new LogTransactionData(
                    userId: new UserId($user->id),
                    amount: $amount->getValue(),
                    direction: Direction::Debit,
                    type: $type,
                    asset: AssetType::Rial,
                    description: $description ?? 'Wallet charge',
                    source: $source
                )
            );

            $wallet->balance = $balance->subtract($amount);
            $wallet->save();
        }, 3);
    }

    public function deposit(User $user, RialAmount $amount, TransactionType $type = TransactionType::TRADE, ?string $description = null, $source = null): void
    {
        \DB::transaction(function () use ($user, $amount, $type, $description, $source) {
            $wallet = $user->wallet()->lockForUpdate()->first();

            $this->transactionService->log(
                new LogTransactionData(
                    userId: new UserId($user->id),
                    amount: $amount->getValue(),
                    direction: Direction::Credit,
                    type: $type,
                    asset: AssetType::Rial,
                    description: $description ?? 'Wallet deposit',
                    source: $source
                )
            );

            $wallet->balance = $this->getBalance($user)->add($amount);
            $wallet->save();
        }, 3);
    }
}
