<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MesocycleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rep_range_min',
        'rep_range_max',
        'typical_duration_weeks',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'rep_range_min' => 'integer',
            'rep_range_max' => 'integer',
            'typical_duration_weeks' => 'integer',
        ];
    }

    public function mesocycles(): HasMany
    {
        return $this->hasMany(Mesocycle::class);
    }
}
