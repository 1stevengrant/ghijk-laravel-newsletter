<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <title>Subscribe to {{ $list->name }}</title>
        
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f8fafc;
                padding: 20px;
            }
            
            .container {
                max-width: 500px;
                margin: 50px auto;
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            
            .header h1 {
                font-size: 24px;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 8px;
            }
            
            .header p {
                color: #6b7280;
                font-size: 16px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 6px;
                font-weight: 500;
                color: #374151;
            }
            
            .form-group input {
                width: 100%;
                padding: 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 16px;
                transition: border-color 0.2s;
            }
            
            .form-group input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            
            .required {
                color: #ef4444;
            }
            
            .submit-btn {
                width: 100%;
                padding: 12px;
                background-color: #3b82f6;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            
            .submit-btn:hover {
                background-color: #2563eb;
            }
            
            .submit-btn:disabled {
                background-color: #9ca3af;
                cursor: not-allowed;
            }
            
            .message {
                margin-top: 16px;
                padding: 12px;
                border-radius: 6px;
                display: none;
            }
            
            .message.success {
                background-color: #d1fae5;
                color: #065f46;
                border: 1px solid #a7f3d0;
            }
            
            .message.error {
                background-color: #fee2e2;
                color: #991b1b;
                border: 1px solid #fecaca;
            }
            
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 14px;
                color: #6b7280;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Subscribe to {{ $list->name }}</h1>
                @if($list->description)
                    <p>{{ $list->description }}</p>
                @endif
            </div>
            
            <form id="newsletter-signup" action="{{ route('newsletter.subscribe', $list->shortcode) }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name">
                </div>
                
                <button type="submit" class="submit-btn">Subscribe to {{ $list->name }}</button>
                
                <div id="message" class="message"></div>
            </form>
            
            <div class="footer">
                <p>You can unsubscribe at any time.</p>
            </div>
        </div>
        
        <script>
            document.getElementById('newsletter-signup').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const form = this;
                const messageDiv = document.getElementById('message');
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                // Reset message
                messageDiv.style.display = 'none';
                messageDiv.className = 'message';
                
                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.textContent = 'Subscribing...';
                
                // Prepare form data
                const formData = new FormData(form);
                
                fetch(form.action, {
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
                        messageDiv.className = 'message success';
                        messageDiv.textContent = data.message;
                        form.reset();
                    } else {
                        throw new Error(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    messageDiv.style.display = 'block';
                    messageDiv.className = 'message error';
                    messageDiv.textContent = error.message || 'An error occurred. Please try again.';
                })
                .finally(() => {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
            });
        </script>
    </body>
</html>