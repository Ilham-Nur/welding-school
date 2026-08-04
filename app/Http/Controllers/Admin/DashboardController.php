<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'participants' => User::role('participant')->count(),
                'pendingApplications' => TrainingApplication::query()
                    ->whereIn('status', ['submitted', 'under_review'])
                    ->count(),
                'activePrograms' => TrainingProgram::query()
                    ->where('status', 'active')
                    ->count(),
                'openBatches' => TrainingBatch::query()
                    ->where('status', 'open')
                    ->count(),
            ],
            'recentApplications' => TrainingApplication::query()
                ->with(['user', 'trainingProgram', 'trainingBatch'])
                ->whereNot('status', 'draft')
                ->latest('submitted_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
