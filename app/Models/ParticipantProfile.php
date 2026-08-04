<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'identity_type',
        'identity_number',
        'birth_place',
        'birth_date',
        'gender',
        'address',
        'city',
        'province',
        'postal_code',
        'last_education',
        'occupation',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleteFor(User $user): bool
    {
        if (! filled($user->username) || ! filled($user->name)) {
            return false;
        }

        foreach ([
            'phone',
            'identity_type',
            'identity_number',
            'birth_place',
            'birth_date',
            'gender',
            'address',
            'city',
            'province',
            'last_education',
            'emergency_contact_name',
            'emergency_contact_phone',
        ] as $attribute) {
            if (! filled($this->{$attribute})) {
                return false;
            }
        }

        return true;
    }
}
