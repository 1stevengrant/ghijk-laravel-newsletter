# Laravel Newsletter

A comprehensive newsletter management system built with Laravel and React, featuring modern email campaign creation, subscriber management, and analytics.

## Features

### ✨ Core Functionality
- **Newsletter List Management** - Create and organize multiple subscriber lists
- **Subscriber Management** - Add, import, and manage subscribers with verification
- **Campaign Creation** - Visual email editor with rich text formatting
- **Email Analytics** - Track opens, clicks, unsubscribes, and bounce rates
- **Scheduled Campaigns** - Queue and schedule email campaigns
- **Responsive Design** - Modern UI built with Tailwind CSS and shadcn/ui

### 📧 Email Features
- **Visual Editor** - TipTap-based rich text editor with formatting options
- **Block System** - Reusable content blocks for email templates
- **Image Management** - Upload and manage campaign images
- **Email Tracking** - Pixel tracking for opens and link tracking for clicks
- **Unsubscribe Handling** - Automated unsubscribe management
- **Queue Processing** - Background email sending with retry logic

### 📊 Analytics & Reporting
- **Campaign Performance** - Open rates, click rates, unsubscribe rates
- **Subscriber Insights** - Track subscriber engagement
- **Dashboard Overview** - Real-time campaign and subscriber statistics

## Tech Stack

- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: React 19 with TypeScript and Inertia.js
- **Styling**: Tailwind CSS v4
- **UI Components**: Radix UI primitives with shadcn/ui patterns
- **Build Tool**: Vite with Laravel plugin
- **Testing**: Pest (PHP), ESLint + Prettier (JS/TS)
- **Email**: Laravel Mail with queue support
- **Database**: SQLite for development

## Getting Started

### Prerequisites
- PHP 8.2+
- Node.js 18+
- Composer
- SQLite

### Installation

1. Clone the repository
```bash
git clone https://github.com/yourusername/laravel-newsletter.git
cd laravel-newsletter
```

2. Install dependencies
```bash
composer install
npm install
```

3. Set up environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Run migrations
```bash
php artisan migrate
```

5. Start development server
```bash
composer dev
```

This will start the Laravel server, queue worker, and Vite development server concurrently.

## Development Commands

### PHP/Laravel
- `composer dev` - Start development server with hot reload
- `composer test` - Run PHP tests using Pest
- `./vendor/bin/pint` - Run Laravel Pint for code formatting

### Frontend
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run types` - Run TypeScript type checking

## Architecture

### Newsletter System
- **NewsletterList**: Container for subscriber groups
- **NewsletterSubscriber**: Individual subscribers with verification tokens
- **Campaign**: Email campaigns with status tracking (draft → scheduled → sending → sent)
- **Image**: File uploads for campaign content

### Campaign Workflow
1. **Create** - Design email content using visual editor
2. **Schedule** - Set send time or send immediately
3. **Queue** - Background job processes email sending
4. **Track** - Monitor opens, clicks, and engagement

### Email Tracking
- **Open Tracking**: Invisible pixel tracking
- **Click Tracking**: Link redirection with tracking
- **Unsubscribe**: One-click unsubscribe with tokens

## Future Roadmap

### 🚀 Phase 1: Enhanced Email Features
- [ ] **Email Templates** - Pre-built responsive email templates
- [ ] **A/B Testing** - Split test subject lines and content
- [ ] **Personalization** - Dynamic content based on subscriber data
- [ ] **Conditional Content** - Show/hide content based on subscriber attributes
- [ ] **Email Validation** - Real-time email validation and verification

### 📊 Phase 2: Advanced Analytics
- [ ] **Heat Maps** - Visual click tracking on emails
- [ ] **Engagement Scoring** - Subscriber engagement metrics
- [ ] **Deliverability Insights** - ISP feedback and reputation tracking
- [ ] **Cohort Analysis** - Subscriber behavior over time
- [ ] **Export Reports** - PDF/CSV export of campaign analytics

### 🔧 Phase 3: Automation & Integration
- [ ] **Drip Campaigns** - Automated email sequences
- [ ] **Trigger-Based Emails** - Welcome series, birthday emails
- [ ] **API Integration** - REST API for third-party integrations
- [ ] **Webhooks** - Real-time event notifications
- [ ] **Zapier Integration** - Connect with 3000+ apps

### 📱 Phase 4: User Experience
- [ ] **Team Management** - Multi-user support with roles
- [ ] **White Label** - Customizable branding options
- [ ] **Multi-language** - Internationalization support

### 🛡️ Phase 5: Advanced Features
- [ ] **GDPR Compliance** - Data protection and consent management
- [ ] **Advanced Segmentation** - Complex subscriber filtering
- [ ] **Machine Learning** - Predictive send times and content optimization
- [ ] **Enterprise Features** - Advanced security and compliance
- [ ] **Multi-tenant** - SaaS-ready architecture

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
