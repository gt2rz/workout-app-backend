<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Macrocycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'goal',
        'start_date',
        'end_date',
        'duration_weeks',
        'is_active',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_weeks' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mesocycles(): HasMany
    {
        return $this->hasMany(Mesocycle::class)->orderBy('order');
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
