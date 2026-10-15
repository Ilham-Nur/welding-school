<?php

namespace App\Services\Training;

use App\Models\TrainingBatch;
use Illuminate\Validation\ValidationException;

class TrainingPricing
{
    public function available(TrainingBatch $batch): bool
    {
        return (! $batch->budget_required && ! $batch->training_budget_id)
            || $batch->trainingBudget?->approved_price !== null;
    }

    public function quote(TrainingBatch $batch): array
    {
        if (! $this->available($batch)) {
            throw ValidationException::withMessages(['training_batch_id' => 'Harga RAB batch ini belum disetujui.']);
        }
        if ($batch->training_budget_id) {
            return [
                'quoted_price' => $batch->trainingBudget->approved_price,
                'quoted_administration_fee' => 0,
                'pricing_snapshot' => ['source' => 'budget', 'budget_id' => $batch->training_budget_id, 'revision' => $batch->trainingBudget->approved_revision],
            ];
        }

        return [
            'quoted_price' => (int) $batch->trainingProgram->price,
            'quoted_administration_fee' => max(0, (int) config('billing.administration_fee')),
            'pricing_snapshot' => ['source' => 'legacy'],
        ];
    }
}
