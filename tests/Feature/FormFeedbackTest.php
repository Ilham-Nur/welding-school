<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_internal_login_use_the_same_feedback_assets(): void
    {
        foreach ([route('home'), route('admin.login'), route('template.components')] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('form-feedback.js')->assertSee('components.js')->assertSee('data-feedback-config', false);
            $this->assertSame(1, substr_count($response->getContent(), 'id="toast-stack"'));
        }
    }

    public function test_server_errors_keep_actionable_detail_outside_the_short_toast_and_escape_content(): void
    {
        $this->withViewErrors(['login' => ['Periksa akun <script>alert(1)</script> Anda.'], 'password' => ['Lengkapi kata sandi terlebih dahulu.']])
            ->blade('<x-ui.feedback />')
            ->assertSee('data-feedback-summary', false)
            ->assertSee('Periksa akun &lt;script&gt;alert(1)&lt;/script&gt; Anda.', false)
            ->assertSee('data-toast="Data belum tersimpan. Periksa bagian yang ditandai pada formulir."', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_web_validation_uses_indonesian_field_names_even_when_default_locale_is_english(): void
    {
        config(['app.locale' => 'en']);
        $this->postJson(route('admin.login.store'), [])->assertUnprocessable()
            ->assertJsonPath('errors.login.0', 'Email atau username wajib diisi.')
            ->assertJsonPath('errors.password.0', 'Password wajib diisi.');
    }

    public function test_core_validation_rules_are_translated_and_do_not_expose_raw_field_keys(): void
    {
        app()->setLocale('id');
        $validator = validator(['training_program_id' => '', 'email' => 'bukan-email', 'photo' => null, 'duration_minutes' => 0], [
            'training_program_id' => 'required', 'email' => 'email', 'photo' => 'required', 'duration_minutes' => 'integer|min:1',
        ]);
        $this->assertSame('Lengkapi program pelatihan terlebih dahulu.', $validator->errors()->first('training_program_id'));
        $this->assertStringContainsString('nama@contoh.id', $validator->errors()->first('email'));
        $this->assertSame('Lengkapi foto terlebih dahulu.', $validator->errors()->first('photo'));
        $this->assertSame('durasi dalam menit minimal 1.', $validator->errors()->first('duration_minutes'));
    }
}
