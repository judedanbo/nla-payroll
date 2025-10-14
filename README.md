# NLA Payroll - Forensic Audit Headcount Application

**National Lottery Authority Ghana - Forensic Audit Headcount Application**

A comprehensive web-based application designed to facilitate forensic audit headcount processes across multiple NLA office locations in Ghana. The system enables coordinated headcount operations, data verification, and discrepancy detection while ensuring data integrity, security, and complete audit trail compliance.

---

## 📊 Project Status

**Current Phase:** Early Development (8 of 245 tasks complete - 3%)

**Implemented Features:**
- ✅ Authentication system with 2FA (Laravel Fortify)
- ✅ User settings management (profile, password, 2FA, appearance/theme)
- ✅ Basic routing structure (web, auth, settings)
- ✅ Frontend architecture (Vue 3 + TypeScript + Inertia.js + Tailwind CSS 4)
- ✅ Development tooling (Pest v4, Laravel Pint, Telescope, Wayfinder)
- ✅ Docker Compose setup for local MySQL database

**Planned Features:**
- Domain models for staff, departments, stations, payments
- Payroll management and verification workflows
- Audit verification with GPS and photo capture
- Excel import/export functionality
- Role-based permissions and access control
- Discrepancy tracking and reporting
- Real-time dashboards and analytics

See `TODOS.md` for complete task breakdown and `technical_blueprint.md` for detailed architecture.

---

## 🛠️ Technology Stack

### Backend
- **Framework:** Laravel 12.33.0
- **Authentication:** Laravel Fortify (with 2FA support)
- **Debugging:** Laravel Telescope 5.14
- **Routing:** Laravel Wayfinder 0.1.9 (type-safe routing)
- **Testing:** Pest 4.1
- **Code Style:** Laravel Pint 1.18
- **Container:** Laravel Sail 1.39

### Frontend
- **Framework:** Vue 3.5.22 + TypeScript
- **Server-Side Rendering:** Inertia.js 2.2.7
- **Build Tool:** Vite 6.0
- **Styling:** Tailwind CSS 4.1.14
- **UI Components:** Reka UI 2.4.1 (headless components)
- **Utilities:** VueUse 12.8.2
- **Linting:** ESLint 9.18 + Prettier 3.4

### Database & Cache
- **Database:** MySQL 8.0+ (or PostgreSQL 14+)
- **Cache/Queue:** Redis
- **ORM:** Eloquent

### Development Environment
- **PHP:** 8.4.13
- **Node.js:** 20.x+
- **Package Managers:** Composer 2.x, npm 10.x

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.4+** with extensions:
  - BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- **Composer 2.x**
- **Node.js 20.x+** and npm
- **MySQL 8.0+** or PostgreSQL 14+
- **Redis** (optional but recommended for caching and queues)
- **Docker & Docker Compose** (optional, for containerized development)

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd nla-payroll
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment

Edit `.env` and configure:

```env
APP_NAME="NLA Forensic Audit System"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nla_payroll
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate

# (Optional) Seed database with test data
php artisan db:seed
```

### 6. Build Frontend Assets

```bash
# For development (with hot-reload)
npm run dev

# For production
npm run build
```

### 7. Start Development Server

#### Option A: Using Laravel Sail (Docker)

```bash
# Start all services (MySQL, Redis, Laravel, Vite)
./vendor/bin/sail up -d

# Run migrations inside container
./vendor/bin/sail artisan migrate
```

#### Option B: Using Composer Scripts

```bash
# Runs server, queue worker, logs, and Vite concurrently
composer run dev
```

#### Option C: Manual

```bash
# In separate terminal windows:
php artisan serve                    # Laravel server
php artisan queue:listen --tries=1   # Queue worker
php artisan pail                     # Log viewer
npm run dev                          # Vite dev server
```

Visit `http://localhost:8000` in your browser.

---

## 🎯 Development Commands

### Setup
```bash
composer run setup  # Complete setup: install deps, copy .env, generate key, migrate, build
```

### Development
```bash
composer run dev      # Start all services (server, queue, logs, vite)
composer run dev:ssr  # Start with SSR support
npm run dev           # Start Vite dev server with HMR
```

### Testing
```bash
composer run test                              # Clear config and run all tests
php artisan test                               # Run all Pest tests
php artisan test tests/Feature/ExampleTest.php # Run specific file
php artisan test --filter=testName             # Run specific test
```

### Code Quality
```bash
vendor/bin/pint --dirty  # Format changed PHP files (run before committing)
npm run lint             # Run ESLint with auto-fix
npm run format           # Format code with Prettier
npm run format:check     # Check formatting without fixing
```

### Building
```bash
npm run build       # Build frontend assets for production
npm run build:ssr   # Build with SSR support
```

### Database
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Drop all tables and re-run migrations
php artisan migrate:fresh --seed # Fresh migrations with seeders
php artisan db:seed              # Run seeders
```

---

## 📁 Project Structure

```
nla-payroll/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Controllers (Auth, Settings)
│   │   ├── Middleware/       # Custom middleware (HandleInertiaRequests, HandleAppearance)
│   │   └── Requests/         # Form request validation classes
│   ├── Models/               # Eloquent models (User.php)
│   └── Providers/            # Service providers
├── bootstrap/
│   ├── app.php              # Middleware & routing registration (Laravel 12 style)
│   └── providers.php        # Application service providers
├── config/                  # Configuration files
├── database/
│   ├── factories/           # Model factories
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── resources/
│   ├── js/
│   │   ├── actions/         # Wayfinder-generated type-safe actions
│   │   ├── components/      # Vue components (ui/, custom)
│   │   ├── composables/     # Vue composables (useAppearance, useTwoFactorAuth)
│   │   ├── layouts/         # Layout components (AppLayout, AuthLayout)
│   │   ├── lib/             # Utility functions
│   │   ├── pages/           # Inertia page components (auth/, settings/)
│   │   ├── routes/          # Route definitions
│   │   ├── types/           # TypeScript type definitions
│   │   ├── wayfinder/       # Wayfinder-generated routing helpers
│   │   ├── app.ts           # Frontend entry point
│   │   └── ssr.ts           # SSR entry point
│   └── css/
│       └── app.css          # Tailwind CSS imports
├── routes/
│   ├── web.php              # Web routes (Inertia)
│   ├── auth.php             # Authentication routes
│   ├── settings.php         # User settings routes
│   └── console.php          # Console commands
├── tests/
│   ├── Feature/             # Feature tests
│   ├── Unit/                # Unit tests
│   └── Browser/             # Browser tests (Pest v4)
├── .env.example             # Environment variables template
├── CLAUDE.md                # Development guide for AI assistants
├── composer.json            # PHP dependencies
├── package.json             # JavaScript dependencies
├── phpunit.xml              # PHPUnit/Pest configuration
├── tailwind.config.js       # Tailwind CSS configuration
├── tsconfig.json            # TypeScript configuration
└── vite.config.ts           # Vite configuration
```

---

## 🧪 Testing

This project uses **Pest v4** for testing.

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Run tests matching a name
php artisan test --filter=login

# Run with coverage report
php artisan test --coverage
```

### Test Types

- **Unit Tests** (`tests/Unit/`): Test individual classes and methods
- **Feature Tests** (`tests/Feature/`): Test complete features and workflows
- **Browser Tests** (`tests/Browser/`): End-to-end browser testing with Pest v4

### Writing Tests

Pest tests use a simple, expressive syntax:

```php
it('authenticates users with valid credentials', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});
```

---

## 🏗️ Architecture Overview

### Server-Side Rendering with Inertia.js

This application uses **Inertia.js** for server-side routing, NOT a traditional REST API architecture.

- **Routes** return `Inertia::render()` responses that render Vue components
- **State management** via Inertia shared props (NOT Pinia)
- **Type-safe routing** with Laravel Wayfinder
- **Session-based authentication** with Laravel Fortify

### Authentication Flow

1. Laravel Fortify handles authentication logic
2. Multi-factor authentication (2FA) via TOTP (Google Authenticator)
3. Session-based authentication (NOT token-based)
4. Middleware: `auth`, `verified`, custom middleware for 2FA

### Frontend Architecture

- **Vue 3 Composition API** with TypeScript
- **Reka UI** headless components (similar to Radix UI)
- **Tailwind CSS 4** for styling with dark mode support
- **Inertia.js** for seamless SPA-like experience without API

---

## 🔐 Security & Compliance

This application is designed for **forensic audit operations** with strict security requirements:

- **Multi-Factor Authentication (2FA)** mandatory for all users
- **Role-Based Access Control (RBAC)** with granular permissions
- **Audit Trail** - All actions logged with tamper-proof logging
- **Data Encryption** - Sensitive fields encrypted at rest (AES-256)
- **TLS 1.3** for data in transit
- **Session Management** - 15-minute timeout, IP whitelisting
- **Compliance** - Ghana Data Protection Act 2012, GDPR principles

**IMPORTANT:** This application handles sensitive personally identifiable information (PII). All developers must:
- Sign non-disclosure agreements
- Follow secure coding practices (OWASP Top 10)
- Never commit sensitive data or credentials to version control
- Run security scans before deployment

---

## 🤝 Contributing

### Code Style

- **PHP:** Follow PSR-12 standards. Run `vendor/bin/pint --dirty` before committing
- **JavaScript/TypeScript:** Follow project ESLint config. Run `npm run lint` before committing
- **Formatting:** Use Prettier for consistent formatting

### Commit Messages

Follow conventional commit format:

```
feat: add staff verification with photo capture
fix: resolve 2FA code validation issue
docs: update API documentation
refactor: simplify payment calculation logic
test: add browser tests for login flow
```

### Pull Request Process

1. Create a feature branch: `git checkout -b feature/description`
2. Make your changes and write tests
3. Run tests: `php artisan test`
4. Format code: `vendor/bin/pint --dirty` and `npm run lint`
5. Commit changes with descriptive message
6. Push and create pull request
7. Ensure CI/CD pipeline passes

---

## 📝 Documentation

- **CLAUDE.md** - Development guide and architecture overview
- **technical_blueprint.md** - Complete technical architecture and planned features
- **TODOS.md** - Project task list and progress tracking (not in git)
- **nla_forensic_audit_requirements.md** - Full requirements specification (not in git)

For Laravel-specific guidance, use the `laravel-boost` MCP tools:
```bash
# List available Artisan commands
php artisan list

# Search documentation
# (Available via MCP tool when using Claude Code)
```

---

## 🐛 Debugging

### Laravel Telescope

Telescope is enabled in development mode for debugging:

- Visit `/telescope` to access the dashboard
- Monitor requests, queries, jobs, exceptions, logs, and more

### Browser Logs

Frontend errors are logged to the Laravel backend:

```bash
# View browser logs (via Laravel Boost MCP)
# Available when using Claude Code with Laravel Boost
```

### Common Issues

**"Unable to locate file in Vite manifest"**
```bash
npm run build
# or
npm run dev
```

**Authentication not working**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 📄 License

**CONFIDENTIAL - PROPRIETARY**

This software is the property of the National Lottery Authority Ghana. It is provided for authorized personnel only and may not be distributed, modified, or reproduced without explicit written permission.

This application contains sensitive data and is subject to:
- Ghana Data Protection Act, 2012
- Non-disclosure agreements
- Security clearance requirements
- Audit compliance regulations

**Unauthorized access, use, or disclosure is strictly prohibited and may result in legal action.**

---

## 📞 Support

For technical support or questions:

- **Technical Lead:** [TBD]
- **Project Manager:** [TBD]
- **Security Officer:** [TBD]

---

**Project Code:** NLA-FA-HC-2025
**Last Updated:** October 14, 2025
**Document Version:** 1.0

---

*This README is part of the NLA Forensic Audit Headcount Application project documentation. For complete architectural details, see `technical_blueprint.md`.*
