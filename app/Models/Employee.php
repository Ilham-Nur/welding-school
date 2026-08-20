<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory;

    public const EMPLOYMENT_STATUSES = [
        'tetap' => 'Karyawan Tetap',
        'kontrak' => 'Karyawan Kontrak',
        'magang' => 'Magang / Internship',
        'freelance' => 'Freelance / Paruh Waktu',
        'harian' => 'Pekerja Harian',
    ];

    public const GENDERS = [
        'laki-laki' => 'Laki-laki',
        'perempuan' => 'Perempuan',
    ];

    public const MARITAL_STATUSES = [
        'lajang' => 'Lajang / Belum Kawin',
        'kawin' => 'Kawin / Menikah',
        'cerai' => 'Cerai',
    ];

    public const RELIGIONS = [
        'Islam' => 'Islam',
        'Kristen' => 'Kristen Protestan',
        'Katolik' => 'Katolik',
        'Hindu' => 'Hindu',
        'Buddha' => 'Buddha',
        'Konghucu' => 'Konghucu',
        'Lainnya' => 'Lainnya',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function positionRelation(): BelongsTo
    {
        return $this->belongsTo(EmployeePosition::class, 'position_id');
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        return asset($this->photo_path);
    }

    public function originalPhotoUrl(): ?string
    {
        if (! $this->original_photo_path) {
            return $this->photoUrl();
        }

        if (str_starts_with($this->original_photo_path, 'http://') || str_starts_with($this->original_photo_path, 'https://')) {
            return $this->original_photo_path;
        }

        return asset($this->original_photo_path);
    }

    public function employmentStatusLabel(): string
    {
        return self::EMPLOYMENT_STATUSES[$this->employment_status] ?? ucfirst((string) $this->employment_status);
    }

    public function genderLabel(): string
    {
        return self::GENDERS[$this->gender] ?? ucfirst((string) $this->gender);
    }

    public function maritalStatusLabel(): string
    {
        return self::MARITAL_STATUSES[$this->marital_status] ?? ucfirst((string) $this->marital_status);
    }
}
