<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Url extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_url',
        'short_code',
        'title',
        'click_count',
        'last_clicked_at',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'last_clicked_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'click_count' => 'integer',
    ];

    /**
     * Get the user that owns the URL.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all clicks for this URL.
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(UrlClick::class);
    }

    /**
     * Get the full short URL.
     */
    public function getShortUrlAttribute(): string
    {
        return url($this->short_code);
    }

    /**
     * Check if the URL is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope to get active URLs only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Increment click count and update last clicked timestamp.
     */
    public function incrementClicks(): void
    {
        $this->increment('click_count');
        $this->update(['last_clicked_at' => now()]);
    }
}
