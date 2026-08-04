<?php

namespace App\Http\Controllers;

use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');

        $programs = TrainingProgram::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when(
                in_array($status, ['active', 'draft', 'closed'], true),
                fn ($query) => $query->where('status', $status),
            )
            ->orderBy('title')
            ->paginate(5)
            ->withQueryString();

        return view('templates.components', compact('programs', 'search', 'status'));
    }
}
