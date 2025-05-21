<?php

namespace App\Services;

use App\DataTransferObjects\LogTransactionData;
use App\Models\Transaction;

class TransactionService
{
    public function log(LogTransactionData $data): void
    {
        Transaction::create([
            'user_id'     => $data->userId->value(),
            'type'        => $data->type,
            'asset'       => $data->asset,
            'direction'   => $data->direction,
            'amount'      => $data->amount,
            'description' => $data->description,
            'source_type' => $data->source?->getMorphClass(),
            'source_id'   => $data->source?->id,
        ]);
    }
}
