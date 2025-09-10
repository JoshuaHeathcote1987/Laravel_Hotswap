<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class HotswapControllerCommand extends Command
{
    protected $signature = 'hotswap:controller {package} {name} {--r}';
    protected $description = 'Create a controller inside a package';

    public function handle()
    {
        $package = Str::lower($this->argument('package'));   // ecommerce
        $studlyPackage = Str::studly($package);              // Ecommerce
        $name = Str::studly($this->argument('name')) . 'Controller';

        $basePath = base_path("packages/{$package}/src/App/Http/Controllers");
        $controllerPath = "{$basePath}/{$name}.php";

        $params = [
            'name' => "Packages\\{$studlyPackage}\\Src\\App\\Http\\Controllers\\{$name}",
        ];
        if ($this->option('r')) $params['--resource'] = true;

        Artisan::call('make:controller', $params);

        $this->info(Artisan::output());

        // Move file into package
        $laravelControllerPath = app_path("Http/Controllers/{$name}.php");
        if (file_exists($laravelControllerPath)) {
            if (!is_dir($basePath)) {
                mkdir($basePath, 0755, true);
            }
            rename($laravelControllerPath, $controllerPath);
            $this->info("✅ Controller moved into {$controllerPath}");
        }
    }
}
