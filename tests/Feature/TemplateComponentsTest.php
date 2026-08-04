<?php

namespace Tests\Feature;

use Database\Seeders\TrainingProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_uses_the_welding_school_template(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('WELDING SCHOOL')
            ->assertSee('public-nav__account')
            ->assertSee('templates/welding-school/style.css')
            ->assertSee('templates/welding-school/app.js');
    }

    public function test_local_cloudflare_proxy_generates_https_asset_urls(): void
    {
        $this
            ->withServerVariables([
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_HOST' => 'tunnel.example.test',
                'HTTP_X_FORWARDED_PORT' => '443',
                'HTTP_X_FORWARDED_PROTO' => 'https',
            ])
            ->get('http://localhost/')
            ->assertOk()
            ->assertSee(
                'https://tunnel.example.test/templates/welding-school/style.css',
                false,
            )
            ->assertSee(
                'https://tunnel.example.test/templates/welding-school/app.js',
                false,
            );
    }

    public function test_component_catalog_renders_all_requested_components(): void
    {
        $this->seed(TrainingProgramSeeder::class);

        $this->get('/template/components')
            ->assertOk()
            ->assertSee('Form dan input data')
            ->assertSee('Input file')
            ->assertSee('Tabel data')
            ->assertSee('Pagination')
            ->assertSee('Modal dan confirmation')
            ->assertSee('Toast')
            ->assertSee('SMAW Welder 3G');
    }

    public function test_component_table_can_be_filtered(): void
    {
        $this->seed(TrainingProgramSeeder::class);

        $response = $this->get('/template/components?search=GTAW&status=draft');

        $response
            ->assertOk()
            ->assertSee('GTAW Welder 6G')
            ->assertSee('Menampilkan 1–1 dari 1 program');

        $this->assertSame(1, $response->viewData('programs')->total());
    }

    public function test_participant_data_and_documents_use_the_dashboard_shell(): void
    {
        $script = file_get_contents(public_path('templates/welding-school/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString(
            'function renderEnrollmentDashboard(activeStep, title, description, content)',
            $script,
        );
        $this->assertStringContainsString(
            'return renderEnrollmentDashboard(',
            $script,
        );
        $this->assertStringContainsString(
            '["dashboard", "member-programs", "registration", "documents"].includes(id)',
            $script,
        );
        $this->assertStringContainsString(
            'function participantSidebar(activeSection)',
            $script,
        );
        $this->assertStringContainsString('"Pendaftaran Saya"', $script);
        $this->assertStringContainsString('"Pembayaran"', $script);
        $this->assertStringContainsString('"Pelatihan Saya"', $script);
        $this->assertStringContainsString(
            'applicationForProgram(selectedProgram)',
            $script,
        );
        $this->assertStringContainsString(
            'training_application_id: state.application?.id',
            $script,
        );
        $this->assertStringContainsString(
            'invoice_id: state.invoice?.id',
            $script,
        );
        $this->assertStringContainsString(
            'data-action="toggle-dashboard-sidebar"',
            $script,
        );
        $this->assertStringContainsString(
            'welding-participant-sidebar',
            $script,
        );
        $this->assertStringContainsString(
            'state.dashboardView === "application-detail"',
            $script,
        );
        $this->assertStringContainsString(
            'state.dashboardView === "training-detail"',
            $script,
        );
        $this->assertStringContainsString(
            'data-action="back-to-applications"',
            $script,
        );
        $this->assertStringContainsString(
            'data-action="back-to-training"',
            $script,
        );
    }

    public function test_dashboard_logout_is_available_only_from_profile_dropdown(): void
    {
        $script = file_get_contents(public_path('templates/welding-school/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('function dashboardAccountMenu()', $script);
        $this->assertStringContainsString('class="profile-dropdown"', $script);
        $this->assertStringContainsString('data-action="toggle-profile-menu"', $script);
        $this->assertStringContainsString('data-action="show-dashboard-profile"', $script);
        $this->assertSame(1, substr_count($script, 'data-action="logout"'));
        $this->assertLessThan(
            strpos($script, 'data-action="logout"'),
            strpos($script, 'data-action="show-dashboard-profile"'),
        );
        $this->assertStringContainsString('state.accountMode = "login";', $script);
        $this->assertStringContainsString('navigate("account");', $script);
    }

    public function test_template_styles_keep_text_and_touch_targets_readable(): void
    {
        foreach (['style.css', 'components.css', 'admin.css'] as $stylesheet) {
            $styles = file_get_contents(
                public_path("templates/welding-school/{$stylesheet}"),
            );

            $this->assertIsString($styles);
            $this->assertDoesNotMatchRegularExpression(
                '/font-size:\s*(?:[7-9]|1[01])px/',
                $styles,
            );
        }

        $publicStyles = file_get_contents(
            public_path('templates/welding-school/style.css'),
        );
        $componentStyles = file_get_contents(
            public_path('templates/welding-school/components.css'),
        );

        $this->assertStringContainsString(
            '@media (min-width: 721px) and (max-width: 860px)',
            $publicStyles,
        );
        $this->assertStringContainsString(
            '.public-nav .public-nav__account',
            $publicStyles,
        );
        $this->assertStringContainsString('min-height: 44px', $componentStyles);
    }

    public function test_program_flow_uses_public_or_dashboard_layout_based_on_login(): void
    {
        $script = file_get_contents(public_path('templates/welding-school/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('function renderDetailPage()', $script);
        $this->assertStringContainsString(
            'if (!state.loggedIn) return renderDetailPage();',
            $script,
        );
        $this->assertStringContainsString('function renderBatchPage()', $script);
        $this->assertStringContainsString(
            'if (!state.loggedIn) return renderBatchPage();',
            $script,
        );
        $this->assertStringContainsString(
            'function renderProgramDashboardPage(',
            $script,
        );
        $this->assertStringContainsString(
            'if (state.loggedIn && steps[index].id === "programs")',
            $script,
        );
        $this->assertStringContainsString(
            '(state.loggedIn && ["detail", "batch"].includes(id))',
            $script,
        );
    }

    public function test_public_home_is_a_company_profile_with_separate_program_page(): void
    {
        $response = $this->get('/');
        $script = file_get_contents(public_path('templates/welding-school/app.js'));

        $response
            ->assertOk()
            ->assertSee('Tentang Kami')
            ->assertSee('data-action="go-programs"', false)
            ->assertSee('data-public-route="programs"', false);

        $this->assertIsString($script);
        $this->assertStringContainsString(
            '{ id: "home", label: "Beranda" }',
            $script,
        );
        $this->assertStringContainsString('function renderHome()', $script);
        $this->assertStringContainsString('class="company-hero"', $script);
        $this->assertStringContainsString('id="about"', $script);
        $this->assertStringContainsString('id="facilities"', $script);
        $this->assertStringContainsString('id="certificate"', $script);
        $this->assertStringContainsString('home: renderHome,', $script);
        $this->assertStringContainsString(
            'if (action === "go-programs")',
            $script,
        );
    }

    public function test_approved_flow_uses_the_persisted_invoice_endpoint(): void
    {
        $response = $this->get('/');
        $script = file_get_contents(public_path('templates/welding-school/app.js'));

        $response
            ->assertOk()
            ->assertSee('invoiceStore');

        $this->assertIsString($script);
        $this->assertStringContainsString(
            'backend.routes?.invoiceStore',
            $script,
        );
        $this->assertStringContainsString(
            'state.invoice = result.invoice;',
            $script,
        );
        $this->assertStringNotContainsString(
            'window.setTimeout(() => navigate("success")',
            $script,
        );
    }
}
