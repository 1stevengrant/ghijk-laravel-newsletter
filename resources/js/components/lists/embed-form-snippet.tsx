import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogHeader, 
    DialogTitle, 
    DialogTrigger 
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Copy, Code, ExternalLink } from 'lucide-react';

interface EmbedFormSnippetProps {
    list: App.Data.NewsletterListData;
}

export function EmbedFormSnippet({ list }: EmbedFormSnippetProps) {
    const [copied, setCopied] = useState(false);
    
    // Don't render if shortcode is not available
    if (!list.shortcode) {
        return null;
    }
    
    const signupUrl = route('newsletter.signup', list.shortcode);
    
    const embedSnippet = `<!-- Newsletter Signup Form for ${list.name} -->
<form id="newsletter-signup-${list.shortcode}" action="${route('newsletter.subscribe', list.shortcode)}" method="POST" style="max-width: 400px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label for="email-${list.shortcode}" style="display: block; margin-bottom: 5px; font-weight: bold;">Email Address *</label>
        <input type="email" id="email-${list.shortcode}" name="email" required 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="first_name-${list.shortcode}" style="display: block; margin-bottom: 5px; font-weight: bold;">First Name</label>
        <input type="text" id="first_name-${list.shortcode}" name="first_name" 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="last_name-${list.shortcode}" style="display: block; margin-bottom: 5px; font-weight: bold;">Last Name</label>
        <input type="text" id="last_name-${list.shortcode}" name="last_name" 
               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
    </div>
    <button type="submit" style="width: 100%; padding: 12px; background-color: #007cba; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
        Subscribe to ${list.name}
    </button>
    <div id="newsletter-message-${list.shortcode}" style="margin-top: 10px; padding: 10px; display: none; border-radius: 4px;"></div>
</form>

<script>
(function() {
    document.getElementById('newsletter-signup-${list.shortcode}').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const messageDiv = document.getElementById('newsletter-message-${list.shortcode}');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Subscribing...';
        
        // Prepare form data
        const formData = new FormData(form);
        
        fetch('${route('newsletter.subscribe', list.shortcode)}', {
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
            submitBtn.textContent = 'Subscribe to ${list.name}';
        });
    });
})();
</script>`;

    const handleCopy = async () => {
        try {
            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(embedSnippet);
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
                return;
            }
            
            // Fallback to older method
            const textArea = document.createElement('textarea');
            textArea.value = embedSnippet;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            
            if (successful) {
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            } else {
                throw new Error('Copy command failed');
            }
        } catch (err) {
            console.error('Failed to copy text: ', err);
            // Could show a toast notification here instead
        }
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <Code className="h-4 w-4 mr-2" />
                    Get Embed Code
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Embed Signup Form</DialogTitle>
                    <DialogDescription>
                        Copy this HTML code to embed a signup form for "{list.name}" on any website.
                        Shortcode: <code className="bg-muted px-1 py-0.5 rounded text-sm">{list.shortcode}</code>
                    </DialogDescription>
                </DialogHeader>
                
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h3 className="text-sm font-medium">Signup Page URL</h3>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.open(signupUrl, '_blank')}
                        >
                            <ExternalLink className="h-4 w-4 mr-2" />
                            Preview
                        </Button>
                    </div>
                    <div className="bg-muted p-3 rounded text-sm font-mono break-all">
                        {signupUrl}
                    </div>
                    
                    <div className="flex items-center justify-between">
                        <h3 className="text-sm font-medium">HTML Embed Code</h3>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleCopy}
                        >
                            <Copy className="h-4 w-4 mr-2" />
                            {copied ? 'Copied!' : 'Copy Code'}
                        </Button>
                    </div>
                    <Textarea
                        value={embedSnippet}
                        readOnly
                        className="font-mono text-xs"
                        rows={20}
                    />
                    
                    <div className="bg-blue-50 p-4 rounded-md">
                        <h4 className="text-sm font-medium text-blue-900 mb-2">Instructions:</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• Copy the HTML code above and paste it into any website</li>
                            <li>• The form will work on any domain without additional setup</li>
                            <li>• Subscribers will be automatically added to "{list.name}"</li>
                            <li>• The form includes client-side validation and success/error handling</li>
                        </ul>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}