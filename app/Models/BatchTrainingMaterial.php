<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchTrainingMaterial extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['participant_visible' => 'boolean'];
    }

    public function revision()
    {
        return $this->belongsTo(TrainingMaterialRevision::class, 'training_material_revision_id');
    }

    public function session()
    {
        return $this->belongsTo(BatchSession::class, 'batch_session_id');
    }

    public function batch()
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }
}
