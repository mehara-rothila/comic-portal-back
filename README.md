# Comic Portal Backend - Technical Setup Guide

This technical guide provides comprehensive instructions for setting up, configuring, and running the Laravel backend for the Comic Portal application.

## 📋 Table of Contents

- [System Requirements](#system-requirements)
- [Project Structure](#project-structure)
- [Installation Steps](#installation-steps)
- [Database Setup](#database-setup)
- [API Endpoints](#api-endpoints)
- [Authentication](#authentication)
- [File Storage Configuration](#file-storage-configuration)
- [Seeding Data](#seeding-data)
- [Running the Backend](#running-the-backend)
- [Troubleshooting](#troubleshooting)
- [Development Workflow](#development-workflow)

## 🖥️ System Requirements

- PHP 8.2+
- Composer
- MySQL 5.7+ or SQLite
- Node.js & NPM (for frontend integration)
- Git

## 📂 Project Structure

The Comic Portal backend follows the standard Laravel architecture with these key components:

```
comic-portal/
├── app/                        # Core application code
│   ├── Http/                   
│   │   ├── Controllers/        # API Controllers
│   │   │   ├── API/            # API Endpoints controllers
│   │   │   └── Controller.php  # Base controller
│   │   └── Middleware/         # Custom middleware
│   │       ├── AdminMiddleware.php  # Admin access control
│   │       └── Authenticate.php     # Authentication
│   ├── Models/                 # Database models
│   │   ├── Comic.php           # Comic model
│   │   ├── Category.php        # Category model
│   │   └── User.php            # User model
│   └── Providers/              # Service providers
├── bootstrap/                  # App bootstrap files
├── config/                     # Configuration files
│   ├── app.php                 # App configuration
│   ├── auth.php                # Authentication config
│   ├── cors.php                # CORS settings
│   └── database.php            # Database config
├── database/                   # Database files
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
│       ├── AdminUserSeeder.php # Admin user seeder
│       ├── CategorySeeder.php  # Categories seeder
│       └── ComicSeeder.php     # Comics seeder
├── public/                     # Publicly accessible files
│   └── images/                 # Comic images storage
├── routes/                     # Route definitions
│   ├── api.php                 # API routes
│   └── web.php                 # Web routes
├── storage/                    # App storage
├── .env.example                # Environment example
├── composer.json               # PHP dependencies
└── artisan                     # CLI tool
```

## 🔧 Installation Steps

1. **Clone the repository**

```bash
git clone https://github.com/your-username/comic-portal.git
cd comic-portal/back-end
```

2. **Install PHP dependencies**

```bash
composer install
```

3. **Create and configure environment file**

```bash
cp .env.example .env
```

4. **Generate application key**

```bash
php artisan key:generate
```

5. **Configure environment variables**

Edit the `.env` file and update these settings:

```
APP_NAME="Comic Portal API"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=comic_portal
DB_USERNAME=root
DB_PASSWORD=

# For SQLite (alternative)
# DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite

# CORS settings for Vue.js frontend
CORS_PATHS=api/*
CORS_ALLOWED_ORIGINS=http://localhost:5173
```

## 💾 Database Setup

### Option 1: MySQL

1. **Create a new MySQL database**

```sql
CREATE DATABASE comic_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Run migrations**

```bash
php artisan migrate
```

### Option 2: SQLite (simpler setup)

1. **Create SQLite database file**

```bash
touch database/database.sqlite
```

2. **Update .env file**

```
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

3. **Run migrations**

```bash
php artisan migrate
```

## 🌐 API Endpoints

The Comic Portal API provides the following endpoints:

### Authentication

- `POST /api/register` - Register a new user
- `POST /api/login` - Login a user
- `POST /api/logout` - Logout a user (requires auth)
- `GET /api/user` - Get authenticated user profile (requires auth)
- `GET /api/check-admin` - Check if user is admin (requires auth)

### Comics

- `GET /api/comics` - List all comics
- `GET /api/comics/{id}` - Get a specific comic
- `GET /api/comics/featured` - Get featured comics
- `GET /api/comics/by-category/{id}` - Get comics by category
- `GET /api/user/comics` - Get user's comics (requires auth)

### Categories

- `GET /api/categories` - List all categories

### Admin Only (requires admin privileges)

- `GET /api/admin/comics` - List all comics (admin view)
- `POST /api/admin/comics` - Create a new comic
- `PUT /api/admin/comics/{comic}` - Update a comic
- `DELETE /api/admin/comics/{comic}` - Delete a comic
- `GET /api/admin/stats` - Get admin dashboard statistics
- `GET /api/admin/users` - List all users

## 🔐 Authentication

The Comic Portal uses Laravel Sanctum for API authentication with token-based access:

1. **Configure Sanctum**

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

2. **Update CORS configuration**

In `config/cors.php`:

```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:5173'], // Frontend URL
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

3. **Ensure Sanctum middleware is enabled**

In `app/Http/Kernel.php`:

```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

## 📁 File Storage Configuration

Comic images are stored in the public directory:

1. **Create image storage directory**

```bash
mkdir -p public/images
chmod 755 public/images
```

2. **Configure file permissions**

Ensure the web server can write to this directory:

```bash
sudo chown -R $USER:www-data public/images
```

## 🌱 Seeding Data

1. **Seed the database with initial data**

```bash
php artisan db:seed
```

This will create:
- Admin user (email: admin3@admin.com, password: rothila123)
- Categories (Action, Fantasy, Superhero, etc.)
- Sample comics

2. **Customizing seeders**

To modify the default data, edit the seeder files:
- `database/seeders/AdminUserSeeder.php`
- `database/seeders/CategorySeeder.php`
- `database/seeders/ComicSeeder.php`
- `database/seeders/NewComicsSeeder.php`

## 🚀 Running the Backend

1. **Start the development server**

```bash
php artisan serve
```

This will start the server at http://localhost:8000

2. **Using Laravel Sail (Docker alternative)**

If you prefer Docker:

```bash
./vendor/bin/sail up
```

3. **Convenient development workflow**

The `composer.json` includes a useful `dev` script that runs multiple services:

```bash
composer run dev
```

This starts:
- Laravel server
- Queue worker
- Log watcher
- Frontend development server (if in same repository)

## ⚠️ Troubleshooting

### CORS Issues

If you encounter CORS errors when connecting from the frontend:

1. Check your `.env` and `config/cors.php` settings
2. Ensure `allowed_origins` includes your frontend URL
3. Verify `supports_credentials` is set to `true`
4. Clear config cache:

```bash
php artisan config:clear
```

### Authentication Problems

If login or registration isn't working:

1. Check database migrations:

```bash
php artisan migrate:status
```

2. Verify Sanctum is properly configured:

```bash
php artisan route:list --path=sanctum
```

3. Ensure the API is returning proper error messages

### File Upload Issues

If image uploads are failing:

1. Check directory permissions:

```bash
ls -la public/images
```

2. Verify PHP file upload settings in `php.ini`:
   - `post_max_size`
   - `upload_max_filesize`

3. Check Laravel validation rules for file uploads in `ComicController.php`

## 🔄 Development Workflow

### API Testing

Use Postman or similar tools to test API endpoints:

1. **Create Collection**:
   - Set up a Postman collection for Comic Portal API
   - Create environment variables for base URL and tokens

2. **Authentication Flow**:
   - Register or login to get an authentication token
   - Store token in environment variables
   - Use token for authenticated requests

3. **Test Admin Functions**:
   - Login as admin (admin3@admin.com / rothila123)
   - Test admin-only endpoints

### Database Management

Useful Artisan commands:

```bash
# Reset and re-run all migrations
php artisan migrate:fresh

# Reset, migrate and seed
php artisan migrate:fresh --seed

# Create a new migration
php artisan make:migration add_field_to_comics_table

# Create a new model with migration and controller
php artisan make:model NewModel -mc
```

### Debugging

For debugging issues:

1. **Check Laravel logs**:

```bash
tail -f storage/logs/laravel.log
```

2. **Use Laravel's debug tools**:

```php
// In controllers or models:
Log::info('Debug message', ['data' => $variable]);
```

3. **Database query logging**:

```php
\DB::enableQueryLog();
// ... code with queries
dd(\DB::getQueryLog());
```

---

With this guide, you should be able to set up, configure, and run the Comic Portal backend successfully. For more advanced configuration options, refer to the Laravel documentation.
