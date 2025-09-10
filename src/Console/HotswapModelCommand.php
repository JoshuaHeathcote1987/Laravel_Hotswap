<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class HotswapModelCommand extends Command
{
    protected $signature = 'hotswap:model {package} {name} {--mcr}';
    protected $description = 'Create a model (optionally with migration, controller, and resource) inside a package';

    public function handle()
    {
        $package = Str::lower($this->argument('package')); // ecommerce
        $name    = Str::studly($this->argument('name'));   // Product
        $withMcr = $this->option('mcr');

        $basePath = base_path("packages/{$package}/src");
        $modelPath = "{$basePath}/App/Models";

        if (!is_dir($modelPath)) {
            mkdir($modelPath, 0755, true);
        }

        // 🟢 Always create the model
        Artisan::call('make:model', [
            'name' => "Packages\\{$package}\\Src\\App\\Models\\{$name}",
        ]);
        $this->info(Artisan::output());
        $this->info("✅ Model {$name} created in {$modelPath}");

        // 🔹 If -mcr is set, also create migration + controller + resource
        if ($withMcr) {
            // Migration
            $migrationName = "create_" . Str::snake(Str::pluralStudly($name)) . "_table";
            $migrationPath = "{$basePath}/databases/migrations";

            if (!is_dir($migrationPath)) {
                mkdir($migrationPath, 0755, true);
            }

            Artisan::call('make:migration', [
                'name' => $migrationName,
                '--path' => "packages/{$package}/src/databases/migrations",
            ]);
            $this->info(Artisan::output());
            $this->info("✅ Migration {$migrationName} created in {$migrationPath}");

            // Controller
            $controllerPath = "{$basePath}/App/Http/Controllers";
            if (!is_dir($controllerPath)) {
                mkdir($controllerPath, 0755, true);
            }

            Artisan::call('make:controller', [
                'name' => "Packages\\{$package}\\Src\\App\\Http\\Controllers\\{$name}Controller",
                '--resource' => true,
            ]);
            $this->info(Artisan::output());
            $this->info("✅ Controller {$name}Controller created in {$controllerPath}");
        }
    }
}
