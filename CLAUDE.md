# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### PHP/Laravel Commands
- `composer dev` - Start development server with hot reload (includes Laravel server, queue worker, logs, and Vite)
- `composer dev:ssr` - Start development server with SSR support  
- `composer test` - Run PHP tests using Pest
- `php artisan test` - Run Laravel tests directly
- `./vendor/bin/pest` - Run Pest tests directly
- `./vendor/bin/pint` - Run Laravel Pint for code formatting

### Frontend Commands
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check code formatting
- `npm run types` - Run TypeScript type checking

### Testing
- Uses Pest PHP testing framework
- Feature tests are in `tests/Feature/`
- Unit tests are in `tests/Unit/`
- Tests include authentication, settings, and core functionality

## Newsletter Application Overview

This is a comprehensive newsletter management system built with Laravel and React, featuring:

### Core Features
- **Newsletter List Management**: Create and manage multiple newsletter lists
- **Subscriber Management**: Add, import, and manage subscribers with subscription tracking
- **Campaign Management**: Create, schedule, and send email campaigns
- **Visual Email Editor**: Rich text editor with block-based content creation
- **Email Analytics**: Track opens, clicks, unsubscribes, and bounce rates
- **Responsive Design**: Modern UI built with Tailwind CSS and shadcn/ui

### Newsletter Models & Relationships
- **NewsletterList**: Container for subscriber groups
- **NewsletterSubscriber**: Individual subscribers with verification and unsubscribe tokens
- **Campaign**: Email campaigns with status tracking (draft, scheduled, sending, sent)
- **Image**: File uploads for campaign content

### Campaign System
- **Status Management**: Draft → Scheduled → Sending → Sent workflow
- **Queue-Based Sending**: Background job processing via `SendCampaignJob`
- **Email Tracking**: Open/click tracking with unique tokens
- **Analytics**: Real-time campaign performance metrics

### Email Features
- **Visual Editor**: TipTap-based rich text editor with formatting options
- **Block System**: Reusable content blocks for email templates
- **Image Management**: Upload and manage campaign images
- **Personalization**: Dynamic content based on subscriber data
- **Unsubscribe Handling**: Automated unsubscribe link generation

## Architecture Overview

### Tech Stack
- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: React 19 with TypeScript and Inertia.js
- **Styling**: Tailwind CSS v4
- **UI Components**: Radix UI primitives with shadcn/ui patterns
- **Build Tool**: Vite with Laravel plugin
- **Testing**: Pest (PHP), ESLint + Prettier (JS/TS)
- **Email**: Laravel Mail with queue support
- **Database**: SQLite for development

### Key Patterns

#### Newsletter Architecture
- **Data Transfer Objects**: Campaign, Subscriber, and List data objects
- **Query Builders**: Custom query builder for Campaign model
- **Event-Driven**: Campaign status changes broadcast via events
- **Job Queues**: Background email sending with retry logic
- **Email Tracking**: Pixel tracking for opens, link tracking for clicks

#### Inertia.js Integration
- Uses Inertia.js for SPA-like experience without API layer
- Pages are React components in `resources/js/pages/`
- Shared data handled via `HandleInertiaRequests` middleware
- Routes defined in `routes/web.php` with Inertia::render()

#### Component Structure
- UI components in `resources/js/components/ui/` (shadcn/ui based)
- Newsletter-specific components in `resources/js/components/campaigns/`, `resources/js/components/lists/`, `resources/js/components/subscribers/`
- Editor components in `resources/js/components/editor/`
- Layout components in `resources/js/layouts/`
- TypeScript types in `resources/js/types/`

#### State Management
- Uses React hooks for local state
- Inertia shared data for global state
- Custom hooks in `resources/js/hooks/`

#### Authentication Flow
- Laravel Breeze authentication starter
- Controllers in `app/Http/Controllers/Auth/`
- Auth routes in `routes/auth.php`
- Settings routes in `routes/settings.php`

#### Frontend Configuration
- TypeScript paths: `@/*` maps to `resources/js/*`
- Vite aliases: `ziggy-js` for Laravel route helpers
- ESLint config uses flat config format
- Prettier with Tailwind plugin for class sorting

### Database Schema
- SQLite for development (`database/database.sqlite`)
- Newsletter-specific tables: `newsletter_lists`, `newsletter_subscribers`, `campaigns`, `images`
- Tracking tables: `campaign_opens` (for email analytics)
- Pivot tables for many-to-many relationships

### File Structure Notes
- Laravel follows standard directory structure
- React components use `.tsx` extension
- Shared types centralized in `resources/js/types/index.d.ts`
- Newsletter controllers in `app/Http/Controllers/`
- Newsletter models in `app/Models/`
- Background jobs in `app/Jobs/`
- Email templates in `app/Mail/`