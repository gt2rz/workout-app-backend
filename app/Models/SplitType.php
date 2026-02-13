<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SplitType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'days_per_week',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_per_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
