<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingMaterialRevision extends Model
{
    public const KINDS = ['syllabus' => 'Silabus / rencana pembelajaran', 'theory' => 'Modul teori', 'trainer' => 'Panduan trainer / metode', 'practice' => 'Job sheet praktik', 'support' => 'Dokumen pendukung'];

    public const METHODS = ['theory' => 'Teori', 'demonstration' => 'Demonstrasi', 'guided_practice' => 'Praktik terbimbing', 'independent_practice' => 'Praktik mandiri', 'discussion' => 'Diskusi', 'orientation' => 'Orientasi / safety induction'];

    public const STATUSES = ['draft' => 'Draft', 'published' => 'Terbit', 'archived' => 'Arsip'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function material()
    {
        return $this->belongsTo(TrainingMaterial::class, 'training_material_id');
    }

    public function files()
    {
        return $this->hasMany(TrainingMaterialFile::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batchItems()
    {
        return $this->hasMany(BatchTrainingMaterial::class);
    }
}
