<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PauseModuleCommand extends Command
{
    protected $signature = 'hotswap:pause {name}';
    protected $description = 'Pause a package by commenting out its provider in bootstrap/providers.php';

    public function handle()
    {
        $name = Str::studly($this->argument('name')); // e.g., Ecommerce
        $file = base_path('bootstrap/providers.php');

        if (!File::exists($file)) {
            $this->error("bootstrap/providers.php not found!");
            return 1;
        }

        $contents = File::get($file);

        $activePattern   = "/^\s*{$name}\\\\App\\\\Providers\\\\AppServiceProvider::class,/m";
        $commentedPattern = "/^\s*\/\/\s*{$name}\\\\App\\\\Providers\\\\AppServiceProvider::class,/m";

        // If already commented, nothing to do
        if (preg_match($commentedPattern, $contents)) {
            $this->info("⏸ Module '{$name}' is already paused.");
            return 0;
        }

        // Comment out the active line
        if (preg_match($activePattern, $contents)) {
            $contents = preg_replace($activePattern, "    //{$name}\\App\\Providers\\AppServiceProvider::class,", $contents);
            File::put($file, $contents);
            $this->info("⏸ Module '{$name}' has been paused (provider commented).");
            return 0;
        }

        $this->warn("⚠️ Provider for module '{$name}' not found in bootstrap/providers.php.");
        return 1;
    }
}
