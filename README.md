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
```

## 🔍 Models, Controllers and Migrations

Easily generate models, controllers, and migrations within a specific module (e.g., ecommerce) using the following commands:

Create a Model (with Migration & Controller)
```
php artisan hotswap:model ecommerce Product -mcr
```

Create a Controller
```
php artisan hotswap:controller ecommerce Product
```

Create a Model
```
php artisan hotswap:model ecommerce Product
```

Create a Migration
```
php artisan hotswap:migration ecommerce products
```

⚠️ As a safety precaution run: 
```
composer dump-autoload
```

## 🤖 Other commands

Manage your modules with the following commands:

### Pause a Module
Temporarily disable a module so it cannot be accessed by users (e.g., ecommerce):
```
php artisan hotswap:pause ecommerce
```

### Resume a Module
Re-enable a previously paused module:
```
php artisan hotswap:play ecommerce
```

### Remove a Module
Permanently delete a module:
```
php artisan hotswap:remove ecommerce
```

⚠️ As a safety precaution run: 
```
composer dump-autoload
```

## 📷 Export images

Export images from hotswap module to the root public folder:
```
php artisan vendor:publish --tag=public --force
```

## 📂 What you get

Modular routes

Independent migrations & seeders

Built-in React (Inertia.js) page scaffolding

Automatic Vite config updates

Service provider registration

## 📝 License

Hotswap is open-source software licensed under the MIT license.