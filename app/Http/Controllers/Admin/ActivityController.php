<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $activities = Activity::query()
            ->with('author:id,name')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', $search)
                        ->orWhere('category', 'like', $search)
                        ->orWhere('excerpt', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.activities.index', compact('activities'));
    }

    public function create(): View
    {
        return view('admin.activities.form', ['activity' => new Activity]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['image_path'] = 'storage/'.$request->file('image')->store('activities', 'public');

        try {
            DB::transaction(function () use ($data): void {
                if ($data['is_featured']) {
                    Activity::query()->update(['is_featured' => false]);
                }

                Activity::query()->create($data);
            });
        } catch (Throwable $exception) {
            $this->deleteUploadedImage($data['image_path']);

            throw $exception;
        }

        return to_route('admin.activities.index')
            ->with('success', 'Aktivitas berhasil ditambahkan.');
    }

    public function edit(Activity $activity): View
    {
        return view('admin.activities.form', compact('activity'));
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $data = $this->validated($request, $activity);
        $oldImagePath = $activity->image_path;
        $newImagePath = null;

        if ($request->hasFile('image')) {
            $newImagePath = 'storage/'.$request->file('image')->store('activities', 'public');
            $data['image_path'] = $newImagePath;
        }

        try {
            DB::transaction(function () use ($activity, $data): void {
                if ($data['is_featured']) {
                    Activity::query()
                        ->where('id', '<>', $activity->getKey())
                        ->update(['is_featured' => false]);
                }

                $activity->update($data);
            });
        } catch (Throwable $exception) {
            if ($newImagePath) {
                $this->deleteUploadedImage($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath) {
            $this->deleteUploadedImage($oldImagePath);
        }

        return to_route('admin.activities.index')
            ->with('success', 'Aktivitas berhasil diperbarui.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $imagePath = $activity->image_path;
        $activity->delete();
        $this->deleteUploadedImage($imagePath);

        return to_route('admin.activities.index')
            ->with('success', 'Aktivitas berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Activity $activity = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:80'],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:30000'],
            'image' => [$activity ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'image_position' => ['required', Rule::in(['left center', '25% center', '50% center', '75% center', 'right center', '50% 25%', '50% 40%', '50% 60%', '50% 75%'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['image_alt'] = filled($validated['image_alt'] ?? null)
            ? trim((string) $validated['image_alt'])
            : trim((string) $validated['title']);

        if ($validated['status'] === 'published' && blank($validated['published_at'] ?? null)) {
            $validated['published_at'] = now();
        }

        unset($validated['image']);

        return $validated;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'aktivitas';
        $slug = $base;
        $counter = 2;

        while (Activity::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function deleteUploadedImage(string $path): void
    {
        if (Str::startsWith($path, 'storage/activities/')) {
            Storage::disk('public')->delete(Str::after($path, 'storage/'));
        }
    }
}
