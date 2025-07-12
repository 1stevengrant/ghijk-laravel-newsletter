<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    protected $guarded = [];

    protected $casts = [
        'new_list_data' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function newsletterList(): BelongsTo
    {
        return $this->belongsTo(NewsletterList::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_rows === 0 || $this->total_rows === null) {
            return 0;
        }

        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }
}
