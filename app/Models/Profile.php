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
}
