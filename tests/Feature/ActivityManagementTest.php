<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_and_admin_are_safe_when_activity_data_is_empty(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->assertDatabaseCount('activities', 0);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('uji-kompetensi-smaw');

        $this->actingAs($admin)
            ->get(route('admin.activities.index'))
            ->assertOk()
            ->assertSee('Belum ada aktivitas');
    }

    public function test_latest_activity_list_does_not_exclude_the_featured_activity(): void
    {
        $script = file_get_contents(public_path('templates/welding-school/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString(
            'const filteredActivities = latestAcademyNews.filter',
            $script,
        );
        $this->assertStringNotContainsString(
            'activity.id === featured.id &&',
            $script,
        );
    }

    public function test_activity_detail_has_clear_and_functional_share_actions(): void
    {
        $script = file_get_contents(public_path('templates/welding-school/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('class="academy-article-share"', $script);
        $this->assertStringContainsString('Bagikan ke LinkedIn', $script);
        $this->assertStringContainsString('Bagikan ke Facebook', $script);
        $this->assertStringContainsString('data-action="copy-article-link"', $script);
        $this->assertStringContainsString('url.searchParams.set("activity", article.id)', $script);
    }

    public function test_related_activity_section_is_hidden_when_no_other_activity_exists(): void
    {
        $script = file_get_contents(public_path('templates/welding-school/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('${related.length ? `', $script);
        $this->assertStringContainsString('class="page-shell academy-related-news"', $script);
    }

    public function test_admin_can_upload_activity_and_published_data_appears_on_frontend(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.activities.create'))
            ->assertOk()
            ->assertSee('Upload aktivitas baru')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('Atur posisi foto')
            ->assertSee('id="activity-image-focus"', false)
            ->assertSee('data-activity-cropper', false)
            ->assertSee('activity-focus-', false);

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'title' => 'Pelatihan GTAW untuk peserta industri',
                'category' => 'Pelatihan',
                'excerpt' => 'Peserta mempraktikkan pengelasan presisi melalui pendampingan instruktur.',
                'content' => "Peserta memulai kegiatan dengan pengarahan keselamatan.\n\nPraktik dilanjutkan dengan evaluasi hasil las.",
                'image' => UploadedFile::fake()->image('gtaw-workshop.jpg', 1600, 900),
                'image_alt' => 'Peserta berlatih GTAW di workshop',
                'image_position' => '50% center',
                'status' => 'published',
                'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
                'is_featured' => '1',
            ])
            ->assertRedirect(route('admin.activities.index'));

        $activity = Activity::query()->firstOrFail();

        $this->assertTrue($activity->is_featured);
        $this->assertStringStartsWith('storage/activities/', $activity->image_path);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $activity->image_path));

        $this->get(route('admin.activities.index'))
            ->assertOk()
            ->assertSee('Pelatihan GTAW untuk peserta industri')
            ->assertSee('Aktivitas unggulan');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pelatihan GTAW untuk peserta industri')
            ->assertSee('Peserta berlatih GTAW di workshop');

        $this->post(route('activities.view', $activity))
            ->assertOk()
            ->assertJsonPath('view_count', 1);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'view_count' => 1,
        ]);
    }

    public function test_admin_can_replace_and_delete_an_uploaded_activity_image(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        Storage::disk('public')->put('activities/old.jpg', 'old-image');

        $activity = Activity::query()->create([
            'author_id' => $admin->id,
            'slug' => 'aktivitas-lama',
            'title' => 'Aktivitas lama',
            'category' => 'Pelatihan',
            'excerpt' => 'Ringkasan aktivitas lama.',
            'content' => 'Isi aktivitas lama.',
            'image_path' => 'storage/activities/old.jpg',
            'image_alt' => 'Aktivitas lama',
            'image_position' => '50% center',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.activities.update', $activity), [
                'title' => 'Aktivitas diperbarui',
                'category' => 'Safety',
                'excerpt' => 'Ringkasan aktivitas yang sudah diperbarui.',
                'content' => 'Isi aktivitas yang sudah diperbarui.',
                'image' => UploadedFile::fake()->image('new.jpg', 1200, 675),
                'image_alt' => '',
                'image_position' => '50% 40%',
                'status' => 'published',
                'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.activities.index'));

        $activity->refresh();
        Storage::disk('public')->assertMissing('activities/old.jpg');
        Storage::disk('public')->assertExists(str_replace('storage/', '', $activity->image_path));
        $this->assertSame('Aktivitas diperbarui', $activity->image_alt);

        $this->delete(route('admin.activities.destroy', $activity))
            ->assertRedirect(route('admin.activities.index'));

        Storage::disk('public')->assertMissing(str_replace('storage/', '', $activity->image_path));
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    public function test_draft_and_future_activities_are_not_exposed_publicly(): void
    {
        foreach ([
            ['slug' => 'draft-activity', 'title' => 'Aktivitas Draft', 'status' => 'draft', 'published_at' => null],
            ['slug' => 'future-activity', 'title' => 'Aktivitas Terjadwal', 'status' => 'published', 'published_at' => now()->addDay()],
        ] as $data) {
            Activity::query()->create([
                ...$data,
                'category' => 'Testing',
                'excerpt' => 'Ringkasan testing.',
                'content' => 'Isi testing.',
                'image_path' => 'templates/welding-school/assets/images/activity-workshop.jpg',
                'image_alt' => $data['title'],
                'image_position' => '50% center',
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Aktivitas Draft')
            ->assertDontSee('Aktivitas Terjadwal');
    }
}
