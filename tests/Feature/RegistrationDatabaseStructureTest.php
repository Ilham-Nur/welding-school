<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationDatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_flow_tables_are_available(): void
    {
        $expectedTables = [
            'users',
            'email_verification_codes',
            'social_accounts',
            'participant_profiles',
            'training_programs',
            'training_batches',
            'training_applications',
            'application_documents',
            'application_status_histories',
            'invoices',
            'payments',
            'payment_webhooks',
            'enrollments',
            'roles',
            'permissions',
            'model_has_roles',
            'role_has_permissions',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table [{$table}] is missing.");
        }
    }

    public function test_google_login_and_payment_idempotency_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('social_accounts', [
            'provider',
            'provider_user_id',
            'provider_email',
        ]));

        $this->assertTrue(Schema::hasColumns('payment_webhooks', [
            'gateway',
            'event_id',
            'signature_valid',
            'processing_status',
            'payload',
        ]));
    }
}
