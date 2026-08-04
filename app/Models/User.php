<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'email_verified_at', 'status', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmail, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function emailVerificationCode(): HasOne
    {
        return $this->hasOne(EmailVerificationCode::class);
    }

    public function profileAvatarUrl(): ?string
    {
        return $this->socialAccounts()
            ->where('provider', 'google')
            ->value('avatar_url');
    }

    public function primaryRoleName(): string
    {
        return $this->getRoleNames()->first() ?? 'participant';
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin']);
    }

    public function participantProfile(): HasOne
    {
        return $this->hasOne(ParticipantProfile::class);
    }

    public function hasCompleteParticipantProfile(): bool
    {
        $profile = $this->relationLoaded('participantProfile')
            ? $this->participantProfile
            : $this->participantProfile()->first();

        return $profile?->isCompleteFor($this) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function participantProfileData(): array
    {
        $profile = $this->relationLoaded('participantProfile')
            ? $this->participantProfile
            : $this->participantProfile()->first();

        return [
            'complete' => $profile?->isCompleteFor($this) ?? false,
            'username' => $this->username ?? '',
            'full_name' => $profile ? $this->name : '',
            'email' => $this->email,
            'phone' => $profile?->phone ?? '',
            'identity_type' => $profile?->identity_type ?? '',
            'identity_number' => $profile?->identity_number ?? '',
            'birth_place' => $profile?->birth_place ?? '',
            'birth_date' => $profile?->birth_date?->format('Y-m-d') ?? '',
            'gender' => $profile?->gender ?? '',
            'address' => $profile?->address ?? '',
            'city' => $profile?->city ?? '',
            'province' => $profile?->province ?? '',
            'postal_code' => $profile?->postal_code ?? '',
            'last_education' => $profile?->last_education ?? '',
            'occupation' => $profile?->occupation ?? '',
            'emergency_contact_name' => $profile?->emergency_contact_name ?? '',
            'emergency_contact_phone' => $profile?->emergency_contact_phone ?? '',
        ];
    }

    public function trainingApplications(): HasMany
    {
        return $this->hasMany(TrainingApplication::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
