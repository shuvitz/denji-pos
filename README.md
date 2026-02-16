# Denji Skeleton - Laravel + Vue + Shadcn

A standard skeleton for Laravel 12 + Vue 3 + Tailwind 4 + Shadcn Vue projects.

## Stack
- **Framework:** Laravel 12
- **Frontend:** Vue 3 (SPA with Vue Router)
- **Styling:** Tailwind CSS v4
- **UI Components:** Shadcn Vue
- **State Management:** Pinia
- **Database:** SQLite (default)

## Setup

1. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Setup Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Setup Database:**
   Ensure `database/database.sqlite` exists:
   ```bash
   # On Windows (PowerShell)
   New-Item -ItemType File -Path database/database.sqlite
   
   # Run migrations
   php artisan migrate
   ```

## Running the App

Start the development server (Laravel + Vite):

```bash
composer dev
```
*(This commands runs `php artisan serve`, `npm run dev`, and queue workers concurrently)*

Or run them individually:
```bash
# Terminal 1: Backend
php artisan serve

# Terminal 2: Frontend
npm run dev
```

Visit **http://localhost:8000** to see the app.

## Project Structure
- `resources/js/pages` - Vue Page components (Login, Dashboard)
- `resources/js/components/ui` - Shadcn UI components
- `resources/js/stores` - Pinia stores (Auth, UI)
- `app/Http/Controllers/Api/AuthController.php` - API Auth Logic (Session-based)
