<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    /**
     * Genera una nueva API Key única y segura
     */
    public static function generate(
        string $name,
        bool $active = true,
        ?\DateTimeInterface $expiresAt = null
    ): self {
        do {
            $key = 'sk_'.Str::random(32);
        } while (self::where('key', $key)->exists());

        return self::create([
            'key' => $key,
            'name' => $name,
            'active' => $active,
            'expires_at' => $expiresAt,
        ]);
    }
}
