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
        $studly = Str::studly($this->argument('name')); // e.g., Ecommerce
        $file   = base_path('bootstrap/providers.php');

        if (!File::exists($file)) {
            $this->error('bootstrap/providers.php not found!');
            return 1;
        }

        $contents = File::get($file);

        // 1) If it's already active, do nothing
        $activePattern = '/^[ \t]*' .
            preg_quote($studly . '\\App\\Providers\\AppServiceProvider::class,', '/') .
            '[ \t]*$/m';

        if (preg_match($activePattern, $contents)) {
            $this->info("✅ Module '{$studly}' is already active.");
            return 0;
        }

        // 2) Uncomment any commented variant (handles spaces/tabs around // and line)
        $commentedPattern = '/^[ \t]*\/\/[ \t]*(' .
            preg_quote($studly . '\\App\\Providers\\AppServiceProvider::class,', '/') .
            ')[ \t]*$/m';

        $newContents = preg_replace(
            $commentedPattern,
            '    $1',
            $contents,
            1, // only the first matching line
            $replacements
        );

        if ($replacements > 0) {
            File::put($file, $newContents);
            $this->info("✅ Module '{$studly}' has been resumed (provider uncommented).");
            return 0;
        }

        $this->warn("⚠️ Could not find a commented provider for '{$studly}' in bootstrap/providers.php.");
        return 1;
    }
}
