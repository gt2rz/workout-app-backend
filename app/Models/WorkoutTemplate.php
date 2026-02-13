<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'split_type_id',
        'user_id',
        'name',
        'day_of_week',
        'description',
        'is_base_template',
        'estimated_duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_base_template' => 'boolean',
            'estimated_duration_minutes' => 'integer',
        ];
    }

    public function splitType(): BelongsTo
    {
        return $this->belongsTo(SplitType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
