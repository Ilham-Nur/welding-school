<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingMaterialFile extends Model
{
    protected $guarded = ['id'];

    public const TYPES = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'txt' => 'text/plain; charset=UTF-8'];

    public function revision()
    {
        return $this->belongsTo(TrainingMaterialRevision::class, 'training_material_revision_id');
    }

    public function canPreview(): bool
    {
        return isset(self::TYPES[$this->extension]);
    }
}
