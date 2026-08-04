<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\TrainingApplication;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_participant_can_create_only_one_invoice(): void
    {
        $this->travelTo(Carbon::parse('2026-07-31 10:00:00'));

        [$participant, $application] = $this->approvedApplication();

        $firstResponse = $this->actingAs($participant)
            ->postJson(route('invoices.store'), [
                'agreement' => true,
                'training_application_id' => $application->id,
            ])
            ->assertCreated()
            ->assertJsonPath('invoice.subtotal', 3500000)
            ->assertJsonPath('invoice.administration_fee', 150000)
            ->assertJsonPath('invoice.discount_amount', 0)
            ->assertJsonPath('invoice.total_amount', 3650000)
            ->assertJsonPath('invoice.status', 'unpaid');

        $invoiceId = $firstResponse->json('invoice.id');
        $invoice = Invoice::query()->findOrFail($invoiceId);

        $this->assertSame($application->id, $invoice->training_application_id);
        $this->assertSame($participant->id, $invoice->user_id);
        $this->assertTrue(
            $invoice->due_at->equalTo($invoice->issued_at->copy()->addHours(24)),
        );

        $this->postJson(route('invoices.store'), [
            'agreement' => true,
            'training_application_id' => $application->id,
        ])
            ->assertOk()
            ->assertJsonPath('invoice.id', $invoiceId);

        $this->assertDatabaseCount('invoices', 1);

        $this->getJson(route('applications.current'))
            ->assertOk()
            ->assertJsonPath('application.invoice.id', $invoiceId)
            ->assertJsonPath('application.invoice.invoice_number', $invoice->invoice_number);
    }

    public function test_invoice_requires_approval_and_policy_agreement(): void
    {
        $participant = User::factory()->create();
        $program = TrainingProgram::query()->create([
            'code' => 'INV-PENDING',
            'title' => 'Program Menunggu Persetujuan',
            'category' => 'Testing',
            'duration_hours' => 40,
            'price' => 1000000,
            'status' => 'active',
        ]);
        $application = TrainingApplication::query()->create([
            'registration_number' => 'WS-INV-PENDING',
            'user_id' => $participant->id,
            'training_program_id' => $program->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($participant)
            ->postJson(route('invoices.store'), [
                'agreement' => true,
                'training_application_id' => $application->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['application']);

        $application->update([
            'status' => 'approved',
            'verified_at' => now(),
        ]);

        $this->postJson(route('invoices.store'), ['agreement' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['agreement']);

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_invoice_is_created_for_the_selected_program(): void
    {
        [$participant, $firstApplication] = $this->approvedApplication();
        $secondProgram = TrainingProgram::query()->create([
            'code' => 'INV-SECOND',
            'title' => 'Program Invoice Kedua',
            'category' => 'Testing',
            'duration_hours' => 60,
            'price' => 4200000,
            'status' => 'active',
        ]);
        TrainingApplication::query()->create([
            'registration_number' => 'WS-INV-SECOND',
            'user_id' => $participant->id,
            'training_program_id' => $secondProgram->id,
            'status' => 'approved',
            'submitted_at' => now(),
            'verified_at' => now(),
        ]);

        $this->actingAs($participant)
            ->postJson(route('invoices.store'), [
                'agreement' => true,
                'training_application_id' => $firstApplication->id,
            ])
            ->assertCreated()
            ->assertJsonPath('invoice.subtotal', 3500000);

        $this->assertDatabaseHas('invoices', [
            'training_application_id' => $firstApplication->id,
            'subtotal' => 3500000,
        ]);
        $this->assertDatabaseCount('invoices', 1);
    }

    /**
     * @return array{User, TrainingApplication}
     */
    private function approvedApplication(): array
    {
        $participant = User::factory()->create();
        $program = TrainingProgram::query()->create([
            'code' => 'INV-APPROVED',
            'title' => 'Program Invoice',
            'category' => 'Testing',
            'duration_hours' => 80,
            'price' => 3500000,
            'status' => 'active',
        ]);
        $application = TrainingApplication::query()->create([
            'registration_number' => 'WS-INV-APPROVED',
            'user_id' => $participant->id,
            'training_program_id' => $program->id,
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
            'verified_at' => now(),
        ]);

        return [$participant, $application];
    }
}
