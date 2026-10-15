<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramTrainingMaterial extends Model
{
    protected $guarded = ['id'];

    public function revision()
    {
        return $this->belongsTo(TrainingMaterialRevision::class, 'training_material_revision_id');
    }
}
