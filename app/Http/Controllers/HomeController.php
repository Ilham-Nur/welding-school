<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\TrainingProgram;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $catalog = TrainingProgram::query()
            ->where('status', 'active')
            ->with([
                'batches' => fn ($query) => $query
                    ->where('status', 'open')
                    ->withCount([
                        'applications' => fn ($query) => $query
                            ->whereIn('status', ['submitted', 'under_review', 'approved']),
                    ])
                    ->orderBy('start_date'),
            ])
            ->orderBy('title')
            ->get()
            ->map(fn (TrainingProgram $program): array => [
                'id' => $program->id,
                'code' => $program->code,
                'title' => $program->title,
                'category' => $program->category,
                'duration_hours' => $program->duration_hours,
                'price' => $program->price,
                'batches' => $program->batches->map(fn ($batch): array => [
                    'id' => $batch->id,
                    'code' => $batch->code,
                    'name' => $batch->name,
                    'registration_deadline' => $batch->registration_deadline?->format('Y-m-d'),
                    'start_date' => $batch->start_date->format('Y-m-d'),
                    'end_date' => $batch->end_date?->format('Y-m-d'),
                    'capacity' => $batch->capacity,
                    'applications_count' => $batch->applications_count,
                ])->values(),
            ])
            ->values();

        $activities = Activity::query()
            ->published()
            ->orderByDesc('published_at')
            ->limit(100)
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->slug,
                'category' => $activity->category,
                'date' => $activity->published_at->translatedFormat('d F Y'),
                'published_at' => $activity->published_at->toIso8601String(),
                'title' => $activity->title,
                'excerpt' => $activity->excerpt,
                'content' => $activity->content,
                'featured' => $activity->is_featured,
                'image' => $activity->imageUrl(),
                'image_alt' => $activity->image_alt ?: $activity->title,
                'image_position' => $activity->image_position,
                'view_count' => $activity->view_count,
                'view_url' => route('activities.view', $activity),
            ])
            ->values();

        return view('home', compact('catalog', 'activities'));
    }
}
