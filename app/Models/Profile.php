<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'avatar_url',
        'preferences',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userPreferences()
    {
        return $this->hasOne(UserPreferences::class, 'user_id', 'user_id');
    }
}
