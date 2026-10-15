<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingMaterial extends Model
{
    protected $guarded = ['id'];

    public function revisions()
    {
        return $this->hasMany(TrainingMaterialRevision::class);
    }

    public function latestRevision()
    {
        return $this->hasOne(TrainingMaterialRevision::class)->latestOfMany('number');
    }

    public function programs()
    {
        return $this->belongsToMany(TrainingProgram::class, 'training_material_program');
    }
}
