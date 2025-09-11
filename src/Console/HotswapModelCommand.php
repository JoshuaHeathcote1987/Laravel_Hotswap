<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class HotswapModelCommand extends Command
{
    protected $signature = 'hotswap:model {package} {name} {--m|migration} {--c|controller} {--r|resource}';
    protected $description = 'Create a model (and optionally a migration + controller) inside a package';

    public function handle()
    {
        $package = Str::lower($this->argument('package'));       // ecommerce
        $studlyPackage = Str::studly($package);                  // Ecommerce
        $name    = Str::studly($this->argument('name'));         // Product

        $basePath = base_path("packages/{$package}/src");

        // 🔹 Create Model directly in package
        $modelNamespace = "{$studlyPackage}\\App\\Models\\{$name}";
        $modelPath = "packages/{$package}/src/app/Models"; // relative path for Artisan

        Artisan::call('make:model', [
            'name' => $modelNamespace,
            '--quiet' => true,
            '--path' => $modelPath,
        ]);

        $this->info("✅ Model created at {$basePath}/app/Models/{$name}.php");

        // 🔹 Create Migration
        if ($this->option('migration')) {
            $migrationPath = "{$basePath}/databases/migrations";
            if (!is_dir($migrationPath)) mkdir($migrationPath, 0755, true);

            $migrationName = "create_" . Str::snake(Str::pluralStudly($name)) . "_table";

            Artisan::call('make:migration', [
                'name' => $migrationName,
                '--path' => "packages/{$package}/src/databases/migrations",
            ]);
            $this->info("✅ Migration created at {$migrationPath}");
        }

        // 🔹 Create Controller
        if ($this->option('controller')) {
            $controllerNamespace = "{$studlyPackage}\\App\\Http\\Controllers\\{$name}Controller";
            $controllerPath = "packages/{$package}/src/app/Http/Controllers";

            Artisan::call('make:controller', [
                'name' => $controllerNamespace,
                '--model' => $modelNamespace,
                '--resource' => $this->option('resource'),
                '--path' => $controllerPath,
            ]);

            $this->info("✅ Controller created at {$basePath}/app/Http/Controllers/{$name}Controller.php");
        }
    }
}
