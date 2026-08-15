 ins# LumBarong - Authentic Filipino E-Commerce

LumBarong is a premium e-commerce platform dedicated to Filipino artisans, specializing in Barong Tagalog, Filipiniana dresses, and local heritage crafts.

## 🚀 System Overview
This project has been migrated to a modern **Laravel** architecture, providing a robust backend, secure authentication, and a dynamic frontend using Blade and Alpine.js.

### Tech Stack
- **Backend**: Laravel 11 (PHP)
- **Frontend**: Blade Templates, Alpine.js, Tailwind CSS
- **Database**: MySQL (XAMPP)
- **Asset Bundling**: Vite

---

## 🛠️ Installation & Setup

### 1. Prerequisites
- **PHP 8.2+**
- **Composer**
- **Node.js & NPM**
- **XAMPP** (for Apache and MySQL)

### 2. Project Setup
1. **Move to the application directory**:
   ```bash
   cd backend-laravel
   ```
2. **Install Dependencies**:
   ```bash
   composer install
   npm install
   ```
3. **Environment Configuration**:
   The system includes a pre-configured `.env` file for development. Ensure your local database settings in `.env` match your XAMPP configuration.
4. **Generate App Key**:
   ```bash
   php artisan key:generate
   ```
5. **Database Import**:
   - Open **phpMyAdmin**.
   - Create a database named `lumbarong`.
   - Import the `database.sql` file located in the project root.

---

## 💻 Running the Application

### Option A: Using XAMPP (Recommended for Windows)
1. Point your XAMPP Apache virtual host to the `backend-laravel/public` directory.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Access the site via your configured local URL (e.g., `http://localhost/Capstone-projct-main/backend-laravel/public`).

### Option B: Using Artisan Serve (Windows / XAMPP)

1. **Terminal 1: Start Backend Server**
   ```powershell
   cd backend-laravel ; C:\xampp\php\php.exe artisan serve
   ```

2. **Terminal 2: Start Frontend Assets (Vite)**
   ```powershell
   cd backend-laravel ; npm run dev
   ```

---

## 📂 Directory Structure
- `backend-laravel/`: The core Laravel application (Models, Views, Controllers).
- `docs/`: System documentation and walkthroughs.
- `database.sql`: The latest database schema and seed data.

---

## 🛡️ Security Note
The `.env` file containing sensitive credentials has been included in this repository for development/handoff purposes. **For production deployment, ensure these secrets are rotated and kept secure.**
