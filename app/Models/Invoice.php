<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'training_application_id',
        'user_id',
        'subtotal',
        'discount_amount',
        'total_amount',
        'currency',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function trainingApplication(): BelongsTo
    {
        return $this->belongsTo(TrainingApplication::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receiptPayment(): ?Payment
    {
        if ($this->status !== 'paid' || ! $this->paid_at) {
            return null;
        }

        if ($this->relationLoaded('payments')) {
            return $this->payments->filter(fn (Payment $payment) => $payment->status === 'paid'
                && $payment->paid_at && $payment->amount === $this->total_amount && $payment->currency === $this->currency)
                ->sortByDesc('paid_at')->first();
        }

        return $this->payments()->where('status', 'paid')->whereNotNull('paid_at')
            ->where('amount', $this->total_amount)->where('currency', $this->currency)
            ->orderByDesc('paid_at')->orderByDesc('id')->first();
    }
}
