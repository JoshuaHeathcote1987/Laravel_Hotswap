<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PlayModuleCommand extends Command
{
    protected $signature = 'hotswap:play {name}';
    protected $description = 'Resume a paused package by uncommenting its provider in bootstrap/providers.php';

    public function handle()
    {
        $name = Str::studly($this->argument('name')); // e.g., Ecommerce
        $file = base_path('bootstrap/providers.php');

        if (!File::exists($file)) {
            $this->error("bootstrap/providers.php not found!");
            return 1;
        }

        $contents = File::get($file);
        $commentedLine = "//    {$name}\\App\\Providers\\AppServiceProvider::class,";

        // If already active, nothing to do
        if (strpos($contents, "    {$name}\\App\\Providers\\AppServiceProvider::class,") !== false) {
            $this->info("✅ Module '{$name}' is already active.");
            return 0;
        }

        // Replace the commented line with active version
        if (strpos($contents, $commentedLine) !== false) {
            $contents = str_replace($commentedLine, "    {$name}\\App\\Providers\\AppServiceProvider::class,", $contents);
            File::put($file, $contents);
            $this->info("✅ Module '{$name}' has been resumed (provider uncommented).");
            return 0;
        }

        $this->warn("⚠️ Commented provider for module '{$name}' not found in bootstrap/providers.php.");
        return 1;
    }
}
