<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $catalog = app(\App\Services\Training\RegistrationCatalog::class)->programs();

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

        $resetToken = $request->route('token');
        $passwordReset = is_string($resetToken) && $resetToken !== ''
            ? [
                'token' => $resetToken,
                'email' => Str::lower(trim((string) $request->query('email'))),
            ]
            : null;

        return view('home', compact('catalog', 'activities', 'passwordReset'));
    }
}
