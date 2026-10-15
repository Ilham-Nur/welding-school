<?php

namespace App\Services\Training;

use App\Models\TrainingProgram;
use Illuminate\Support\Collection;

class RegistrationCatalog
{
    public function programs(): Collection
    {
        return TrainingProgram::query()
            ->where('status', 'active')
            ->with([
                'batches' => fn ($query) => $query
                    ->where('status', 'open')
                    ->where(fn ($q) => $q->whereNull('registration_deadline')->orWhere('registration_deadline', '>=', now()))
                    ->whereIn('operational_status', ['preparation', 'ready'])
                    ->where(fn ($q) => $q->where(fn ($legacy) => $legacy->where('budget_required', false)->whereNull('training_budget_id'))
                        ->orWhereHas('trainingBudget', fn ($budget) => $budget->whereNotNull('approved_price')))
                    ->with(['trainingBudget', 'sessions' => fn ($q) => $q->where('status', 'published')->with('location')->orderBy('starts_at')])
                    ->withCount([
                        'applications' => fn ($query) => $query
                            ->whereIn('status', ['submitted', 'under_review', 'approved']),
                    ])
                    ->orderBy('start_date'),
            ])
            ->orderBy('title')
            ->get()
            ->each(fn ($program) => $program->setRelation('batches', $program->batches->filter(fn ($batch) => $batch->applications_count < $batch->capacity)->values()))
            ->map(fn (TrainingProgram $program): array => [
                'id' => $program->id,
                'code' => $program->code,
                'title' => $program->title,
                'category' => $program->category,
                'duration_hours' => $program->duration_hours,
                'price' => $program->batches->isNotEmpty() ? $program->batches->min(fn ($batch) => $batch->trainingBudget?->approved_price ?? $program->price) : null,
                'batches' => $program->batches->map(fn ($batch): array => [
                    'id' => $batch->id,
                    'code' => $batch->code,
                    'name' => $batch->name,
                    'price' => $batch->trainingBudget?->approved_price ?? $program->price,
                    'administration_fee' => $batch->training_budget_id ? 0 : max(0, (int) config('billing.administration_fee')),
                    'registration_deadline' => $batch->registration_deadline?->format('Y-m-d'),
                    'start_date' => $batch->start_date->format('Y-m-d'),
                    'end_date' => $batch->end_date?->format('Y-m-d'),
                    'capacity' => $batch->capacity,
                    'locations' => $batch->sessions->pluck('location.name')->filter()->unique()->values(),
                    'applications_count' => $batch->applications_count,
                ])->values(),
            ])
            ->values();
    }
}
