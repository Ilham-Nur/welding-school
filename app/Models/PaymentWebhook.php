<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'gateway_transaction_id',
        'signature_valid',
        'processing_status',
        'payload',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
