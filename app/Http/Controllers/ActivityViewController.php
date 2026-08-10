<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\JsonResponse;

class ActivityViewController extends Controller
{
    public function __invoke(Activity $activity): JsonResponse
    {
        abort_unless(
            $activity->status === 'published'
                && $activity->published_at?->lessThanOrEqualTo(now()),
            404,
        );

        $activity->increment('view_count');

        return response()->json([
            'view_count' => $activity->fresh()->view_count,
        ]);
    }
}
