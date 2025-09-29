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

        /**
         * 🔹 Create Model
         */
        Artisan::call('make:model', [
            'name' => $name,   // always create in default app/Models
            '--quiet' => true,
        ]);

        $defaultModelPath = base_path("app/Models/{$name}.php");
        $targetModelPath  = "{$basePath}/app/Models/{$name}.php";

        $this->moveFile($defaultModelPath, $targetModelPath);

        // Fix namespace in the model file
        if (file_exists($targetModelPath)) {
            $contents = file_get_contents($targetModelPath);
            $contents = str_replace("namespace App\\Models;", "namespace {$studlyPackage}\\App\\Models;", $contents);
            file_put_contents($targetModelPath, $contents);
        }

        $this->info("✅ Model created at {$targetModelPath}");

        /**
         * 🔹 Create Migration
         */
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

        /**
         * 🔹 Create Controller
         */
        if ($this->option('controller')) {
            Artisan::call('make:controller', [
                'name' => "{$name}Controller",  // default path first
                '--model' => "{$studlyPackage}\\App\\Models\\{$name}",
                '--resource' => $this->option('resource'),
                '--quiet' => true, // prevents interactive prompts
            ]);

            $defaultControllerPath = base_path("app/Http/Controllers/{$name}Controller.php");
            $targetControllerPath  = "{$basePath}/app/Http/Controllers/{$name}Controller.php";

            $this->moveFile($defaultControllerPath, $targetControllerPath);

            // Fix namespace in the controller file
            if (file_exists($targetControllerPath)) {
                $contents = file_get_contents($targetControllerPath);
                $contents = str_replace(
                    "namespace App\\Http\\Controllers;",
                    "namespace {$studlyPackage}\\App\\Http\\Controllers;",
                    $contents
                );
                file_put_contents($targetControllerPath, $contents);
            }

            // Fix namespace in the controller file
            if (file_exists($targetControllerPath)) {
                $contents = file_get_contents($targetControllerPath);
                $contents = str_replace(
                    "namespace App\\Http\\Controllers;",
                    "namespace {$studlyPackage}\\App\\Http\\Controllers;",
                    $contents
                );

                // Ensure base Controller is imported
                if (!str_contains($contents, 'use App\\Http\\Controllers\\Controller;')) {
                    $contents = preg_replace(
                        '/namespace [^;]+;/',
                        "namespace {$studlyPackage}\\App\\Http\\Controllers;\n\nuse App\\Http\\Controllers\\Controller;",
                        $contents,
                        1
                    );
                }

                file_put_contents($targetControllerPath, $contents);
            }

            $this->info("✅ Controller created at {$targetControllerPath}");
        }
    }

    protected function moveFile(string $from, string $to)
    {
        if (file_exists($from)) {
            if (!is_dir(dirname($to))) mkdir(dirname($to), 0755, true);
            rename($from, $to);
        }
    }
}
