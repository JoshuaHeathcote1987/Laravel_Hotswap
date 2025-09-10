<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class HotswapMigrationCommand extends Command
{
    protected $signature = 'hotswap:migration {package} {name}';
    protected $description = 'Create a migration inside a package';

    public function handle()
    {
        $package = Str::lower($this->argument('package'));   // ecommerce
        $name = $this->argument('name');                     // create_products_table

        $migrationPath = base_path("packages/{$package}/src/databases/migrations");

        if (!is_dir($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        Artisan::call('make:migration', [
            'name' => $name,
            '--path' => "packages/{$package}/src/databases/migrations",
        ]);

        $this->info(Artisan::output());
        $this->info("✅ Migration created in {$migrationPath}");
    }
}
