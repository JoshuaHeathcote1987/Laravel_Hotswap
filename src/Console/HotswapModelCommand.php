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
        $studlyPackage = Str::studly($package);                 // Ecommerce
        $name    = Str::studly($this->argument('name'));        // Product

        $basePath = base_path("packages/{$package}/src");

        // 🔹 Create Model
        $modelNamespace = "{$studlyPackage}\\App\\Models\\{$name}";
        Artisan::call('make:model', [
            'name' => $modelNamespace,
            '--quiet' => true,
        ]);

        $modelTarget = "{$basePath}/app/Models/{$name}.php";
        $this->moveFile("app/Models/{$name}.php", $modelTarget);
        $this->info("✅ Model created at {$modelTarget}");

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
            Artisan::call('make:controller', [
                'name' => $controllerNamespace,
                '--model' => $modelNamespace,
                '--resource' => $this->option('resource'),
            ]);

            $controllerTarget = "{$basePath}/app/Http/Controllers/{$name}Controller.php";
            $this->moveFile("app/Http/Controllers/{$name}Controller.php", $controllerTarget);
            $this->info("✅ Controller created at {$controllerTarget}");
        }
    }

    protected function moveFile(string $defaultPath, string $targetPath)
    {
        $defaultFull = base_path($defaultPath);
        if (file_exists($defaultFull)) {
            if (!is_dir(dirname($targetPath))) mkdir(dirname($targetPath), 0755, true);
            rename($defaultFull, $targetPath);
        }
    }
}
