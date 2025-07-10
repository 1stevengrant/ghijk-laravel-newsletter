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

## Architecture Overview

### Tech Stack
- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: React 19 with TypeScript and Inertia.js
- **Styling**: Tailwind CSS v4
- **UI Components**: Radix UI primitives with shadcn/ui patterns
- **Build Tool**: Vite with Laravel plugin
- **Testing**: Pest (PHP), ESLint + Prettier (JS/TS)

### Key Patterns

#### Inertia.js Integration
- Uses Inertia.js for SPA-like experience without API layer
- Pages are React components in `resources/js/pages/`
- Shared data handled via `HandleInertiaRequests` middleware
- Routes defined in `routes/web.php` with Inertia::render()

#### Component Structure
- UI components in `resources/js/components/ui/` (shadcn/ui based)
- App-specific components in `resources/js/components/`
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

### Database
- SQLite for development (`database/database.sqlite`)
- Migrations in `database/migrations/`
- Uses Laravel's default user authentication tables

### File Structure Notes
- Laravel follows standard directory structure
- React components use `.tsx` extension
- Shared types centralized in `resources/js/types/index.d.ts`
- Tailwind configured via Vite plugin (no separate config file)