<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mesocycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'macrocycle_id',
        'mesocycle_type_id',
        'split_type_id',
        'order',
        'start_week',
        'duration_weeks',
        'deload_weeks',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'deload_weeks' => 'array',
            'order' => 'integer',
            'start_week' => 'integer',
            'duration_weeks' => 'integer',
        ];
    }

    public function macrocycle(): BelongsTo
    {
        return $this->belongsTo(Macrocycle::class);
    }

    public function mesocycleType(): BelongsTo
    {
        return $this->belongsTo(MesocycleType::class);
    }

    public function splitType(): BelongsTo
    {
        return $this->belongsTo(SplitType::class);
    }

    public function microcycles(): HasMany
    {
        return $this->hasMany(Microcycle::class)->orderBy('week_number');
    }

    public function volumeTargets(): HasMany
    {
        return $this->hasMany(MesocycleVolumeTarget::class);
    }
}
