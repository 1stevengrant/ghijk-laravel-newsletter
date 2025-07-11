<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewsletterList extends Model
{
    /** @use HasFactory<\Database\Factories\NewsletterListFactory> */
    use HasFactory;

    protected $guarded = [];

    public function subscribers()
    {
        return $this->hasMany(NewsletterSubscriber::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
