<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class HotswapModelCommand extends Command
{
    protected $signature = 'hotswap:model {package} {name} {--m} {--c} {--r}';
    protected $description = 'Create a model inside a package with optional migration, controller, resource';

    public function handle()
    {
        $package = Str::lower($this->argument('package'));   // ecommerce
        $studlyPackage = Str::studly($package);              // Ecommerce
        $name = Str::studly($this->argument('name'));        // Product

        $basePath = base_path("packages/{$package}/src/App");
        $modelPath = "{$basePath}/Models/{$name}.php";

        // Run make:model but redirect paths
        $params = [
            'name' => "Packages\\{$studlyPackage}\\Src\\App\\Models\\{$name}",
        ];

        if ($this->option('m')) $params['--migration'] = true;
        if ($this->option('c')) $params['--controller'] = true;
        if ($this->option('r')) $params['--resource'] = true;

        Artisan::call('make:model', $params);

        $this->info(Artisan::output());

        // Move file into the package
        $laravelModelPath = app_path("Models/{$name}.php");
        if (file_exists($laravelModelPath)) {
            if (!is_dir(dirname($modelPath))) {
                mkdir(dirname($modelPath), 0755, true);
            }
            rename($laravelModelPath, $modelPath);
            $this->info("✅ Model moved into {$modelPath}");
        }
    }
}
