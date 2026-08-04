<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_complete_profile_and_personal_data(): void
    {
        $user = User::factory()->create([
            'username' => null,
            'name' => 'Akun Peserta',
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.update'), $this->profilePayload())
            ->assertOk()
            ->assertJsonPath('profile.complete', true)
            ->assertJsonPath('profile.username', 'budi.welder')
            ->assertJsonPath('profile.full_name', 'Budi Santoso');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'budi.welder',
            'name' => 'Budi Santoso',
        ]);
        $this->assertDatabaseHas('participant_profiles', [
            'user_id' => $user->id,
            'identity_number' => '3671000000000001',
            'city' => 'Cilegon',
            'province' => 'Banten',
        ]);
    }

    public function test_empty_profile_is_returned_as_incomplete(): void
    {
        $user = User::factory()->create([
            'username' => 'peserta',
        ]);

        $this->actingAs($user)
            ->getJson(route('profile.show'))
            ->assertOk()
            ->assertJsonPath('profile.complete', false)
            ->assertJsonPath('profile.full_name', '');
    }

    /**
     * @return array<string, string>
     */
    private function profilePayload(): array
    {
        return [
            'username' => 'Budi.Welder',
            'full_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'identity_type' => 'ktp',
            'identity_number' => '3671000000000001',
            'birth_place' => 'Serang',
            'birth_date' => '1998-03-10',
            'gender' => 'male',
            'address' => 'Jalan Industri Nomor 1',
            'city' => 'Cilegon',
            'province' => 'Banten',
            'postal_code' => '42435',
            'last_education' => 'SMA/SMK',
            'occupation' => 'Welder',
            'emergency_contact_name' => 'Andi Santoso',
            'emergency_contact_phone' => '081299999999',
        ];
    }
}
