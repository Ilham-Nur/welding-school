<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoadingSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_internal_pages_load_the_shared_loading_interface(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="global-loading"', false)
            ->assertSee('data-loading-long-message', false)
            ->assertSee('templates/welding-school/loading.js', false);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('id="global-loading"', false)
            ->assertSee('templates/welding-school/loading.js', false);
    }

    public function test_loading_script_supports_forms_navigation_ajax_and_direct_downloads(): void
    {
        $script = file_get_contents(public_path('templates/welding-school/loading.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('window.AppLoading', $script);
        $this->assertStringContainsString('document.addEventListener("submit"', $script);
        $this->assertStringContainsString('[data-loading-download]', $script);
        $this->assertStringContainsString('await fetch(link.href', $script);
        $this->assertStringContainsString('window.addEventListener("pageshow"', $script);
    }

    public function test_component_catalog_includes_a_loading_preview(): void
    {
        $this->get('/template/components')
            ->assertOk()
            ->assertSee('Loading global')
            ->assertSee('data-loading-preview', false);
    }

    public function test_synchronous_exports_opt_in_to_download_loading(): void
    {
        $assetView = file_get_contents(resource_path('views/admin/assets/index.blade.php'));
        $storageView = file_get_contents(resource_path('views/admin/storage/reports/index.blade.php'));

        $this->assertIsString($assetView);
        $this->assertIsString($storageView);
        $this->assertStringContainsString('data-loading-title="Menyiapkan Excel aset"', $assetView);
        $this->assertSame(2, substr_count($storageView, 'data-loading-download'));
    }
}
