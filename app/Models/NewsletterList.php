<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewsletterList extends Model
{
    /** @use HasFactory<\Database\Factories\NewsletterListFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (NewsletterList $list) {
            if (empty($list->shortcode)) {
                $list->shortcode = static::generateUniqueShortcode();
            }
        });
    }

    protected static function generateUniqueShortcode(): string
    {
        do {
            $shortcode = Str::random(8);
        } while (static::where('shortcode', $shortcode)->exists());

        return $shortcode;
    }

    public function subscribers()
    {
        return $this->hasMany(NewsletterSubscriber::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function getEmbedFormSnippet(): string
    {
        $signupUrl = route('newsletter.subscribe', $this->shortcode);
        $signupPageUrl = route('newsletter.signup', $this->shortcode);

        return <<<HTML
<!-- Newsletter Signup Form for {$this->name} -->
<form id="newsletter-signup-{$this->shortcode}" action="{$signupUrl}" method="POST" style="max-width: 400px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label for="email-{$this->shortcode}" style="display: block; margin-bottom: 5px; font-weight: bold;">Email Address *</label>
        <input type="email" id="email-{$this->shortcode}" name="email" required 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="first_name-{$this->shortcode}" style="display: block; margin-bottom: 5px; font-weight: bold;">First Name</label>
        <input type="text" id="first_name-{$this->shortcode}" name="first_name" 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="last_name-{$this->shortcode}" style="display: block; margin-bottom: 5px; font-weight: bold;">Last Name</label>
        <input type="text" id="last_name-{$this->shortcode}" name="last_name" 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
    </div>
    <button type="submit" style="width: 100%; padding: 12px; background-color: #007cba; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
        Subscribe to {$this->name}
    </button>
    <div id="newsletter-message-{$this->shortcode}" style="margin-top: 10px; padding: 10px; display: none; border-radius: 4px;"></div>
</form>

<script>
(function() {
    document.getElementById('newsletter-signup-{$this->shortcode}').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const messageDiv = document.getElementById('newsletter-message-{$this->shortcode}');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Subscribing...';
        
        // Prepare form data
        const formData = new FormData(form);
        
        // Add CSRF token for Laravel
        fetch('{$signupUrl}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.textContent = data.message;
                form.reset();
            } else {
                throw new Error(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            messageDiv.style.display = 'block';
            messageDiv.style.backgroundColor = '#f8d7da';
            messageDiv.style.color = '#721c24';
            messageDiv.style.border = '1px solid #f5c6cb';
            messageDiv.textContent = error.message || 'An error occurred. Please try again.';
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = 'Subscribe to {$this->name}';
        });
    });
})();
</script>

<!-- Alternative: Simple link to hosted signup page -->
<!-- <a href="{$signupPageUrl}" target="_blank">Subscribe to {$this->name}</a> -->
HTML;
    }
}
