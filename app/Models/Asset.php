<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class Asset extends Model
{
    public const TYPE_GENERAL = 'general';

    public const TYPE_MEASURING = 'measuring';

    public const CATEGORIES = [
        'WLD' => 'Welding Equipment',
        'MSR' => 'Measuring / Inspection Tools',
        'NDT' => 'NDT Equipment',
        'TOL' => 'Hand & Power Tools',
        'PPE' => 'Safety Equipment / APD',
        'GAS' => 'Gas Equipment',
        'MAT' => 'Training Supporting Equipment',
        'FAC' => 'Fasilitas / Workshop Equipment',
    ];

    public const CONDITIONS = [
        'good' => 'Baik',
        'fair' => 'Cukup',
        'damaged' => 'Rusak',
    ];

    public const INSPECTION_INTERVALS = [
        1 => 'Setiap 1 bulan',
        2 => 'Setiap 2 bulan',
        12 => 'Setiap 12 bulan',
    ];

    public const STATUSES = [
        'active' => 'Aktif',
        'maintenance' => 'Dalam perbaikan',
        'under_calibration' => 'Dalam kalibrasi',
        'out_of_service' => 'Tidak layak pakai',
        'retired' => 'Tidak digunakan',
    ];

    protected $fillable = [
        'public_id',
        'asset_code',
        'asset_type',
        'category_code',
        'equipment_name',
        'photo_path',
        'brand',
        'model',
        'serial_number',
        'quantity',
        'purchase_year',
        'location',
        'condition',
        'inspection_interval_months',
        'last_inspected_at',
        'next_inspection_at',
        'status',
        'requires_calibration',
        'calibrated_at',
        'calibration_due_at',
        'certificate_number',
        'calibration_certificate_path',
        'calibration_certificate_name',
        'calibration_certificate_mime',
        'calibration_certificate_size',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Asset $asset): void {
            $asset->public_id ??= (string) Str::uuid();
        });

        static::updating(function (Asset $asset): void {
            if ($asset->isDirty(['asset_code', 'category_code'])) {
                throw new LogicException('Asset ID dan kategori tidak dapat diubah setelah aset dibuat.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'purchase_year' => 'integer',
            'inspection_interval_months' => 'integer',
            'last_inspected_at' => 'date',
            'next_inspection_at' => 'date',
            'requires_calibration' => 'boolean',
            'calibrated_at' => 'date',
            'calibration_due_at' => 'date',
            'calibration_certificate_size' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(AssetChecklistItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(AssetInspection::class)->latest('inspected_at');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category_code] ?? (string) $this->category_code;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset($this->photo_path) : null;
    }

    public function calibrationCertificateUrl(): ?string
    {
        return $this->calibration_certificate_path
            ? route('assets.calibration-certificate', ['asset' => $this->public_id])
            : null;
    }

    public function conditionLabel(): string
    {
        return self::CONDITIONS[$this->condition] ?? ucfirst((string) $this->condition);
    }

    public function inspectionIntervalLabel(): string
    {
        return self::INSPECTION_INTERVALS[$this->inspection_interval_months]
            ?? "Setiap {$this->inspection_interval_months} bulan";
    }

    public function inspectionStatusLabel(): string
    {
        if (! $this->next_inspection_at) {
            return 'Belum dijadwalkan';
        }

        if ($this->next_inspection_at->lt(today())) {
            return 'Terlambat';
        }

        if ($this->next_inspection_at->lte(today()->addDays(7))) {
            return 'Segera diperiksa';
        }

        return 'Terjadwal';
    }

    public function inspectionTone(): string
    {
        if (! $this->next_inspection_at) {
            return 'neutral';
        }

        if ($this->next_inspection_at->lt(today())) {
            return 'danger';
        }

        if ($this->next_inspection_at->lte(today()->addDays(7))) {
            return 'warning';
        }

        return 'success';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function effectiveStatus(): string
    {
        return $this->status;
    }

    public function calibrationStatus(): string
    {
        if (! $this->requires_calibration) {
            return 'not_required';
        }

        if ($this->status === 'under_calibration') {
            return 'under_calibration';
        }

        if (! $this->calibrated_at || ! $this->calibration_due_at || ! $this->certificate_number) {
            return 'incomplete';
        }

        if ($this->calibration_due_at->lt(today())) {
            return 'expired';
        }

        if ($this->calibration_due_at->lte(today()->addDays(30))) {
            return 'due_soon';
        }

        return 'valid';
    }

    public function calibrationStatusLabel(): string
    {
        return match ($this->calibrationStatus()) {
            'valid' => 'Valid',
            'due_soon' => 'Segera jatuh tempo',
            'expired' => 'Kedaluwarsa',
            'under_calibration' => 'Dalam kalibrasi',
            'incomplete' => 'Data belum lengkap',
            default => 'Tidak wajib kalibrasi',
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'maintenance', 'under_calibration' => 'info',
            'out_of_service' => 'danger',
            default => 'neutral',
        };
    }

    public function calibrationTone(): string
    {
        return match ($this->calibrationStatus()) {
            'valid' => 'success',
            'due_soon' => 'warning',
            'expired', 'incomplete' => 'danger',
            'under_calibration' => 'info',
            default => 'neutral',
        };
    }

}
