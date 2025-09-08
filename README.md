<p align="center">
  <img src="logo.png" alt="Hotswap Logo" width="256" height="256">
</p>

# Hotswap

Hotswap is a Laravel package that enables modular development by generating self-contained packages with their own routes, migrations, service providers, and frontend scaffolding. It’s designed to make building reusable Laravel + Inertia + Vite modules simple and fast.

## 🚀 Installation

Install the package via Packagist
```
composer require joshlogic/hotswap:dev-main
```

Publish the core scaffolding:
```
php artisan hotswap:scaffold
```

## ⚡ Usage

Create a new module (e.g., ecommerce):
```
php artisan hotswap:create ecommerce
```
Rebuild autoload files:
```
composer dump-autoload
```
Compile frontend assets:
```
npm run build
```
Run the development server:
```
php artisan serve  
npm run dev
```
## 📂 What you get

Modular routes

Independent migrations & seeders

Built-in React (Inertia.js) page scaffolding

Automatic Vite config updates

Service provider registration

## 📝 License

Hotswap is open-source software licensed under the MIT license.