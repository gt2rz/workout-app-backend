<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MesocycleVolumeTarget extends Model
{
    protected $fillable = [
        'mesocycle_id',
        'muscle_group_id',
        'mev',
        'mav_min',
        'mav_max',
        'mrv',
        'mv',
        'current_weekly_volume',
    ];

    protected function casts(): array
    {
        return [
            'mev' => 'integer',
            'mav_min' => 'integer',
            'mav_max' => 'integer',
            'mrv' => 'integer',
            'mv' => 'integer',
            'current_weekly_volume' => 'integer',
        ];
    }

    public function mesocycle(): BelongsTo
    {
        return $this->belongsTo(Mesocycle::class);
    }

    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }
}
