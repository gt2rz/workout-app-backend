<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
        protected $fillable = [
            'key',
            'name',
            'active',
            'expires_at',
        ];

        protected $casts = [
            'active' => 'boolean',
            'expires_at' => 'datetime',
        ];

        public static function findActive(string $key): ?self
        {
            return self::where('key', $key)
                ->where('active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();
        }
}
