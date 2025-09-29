<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RemoveModuleCommand extends Command
{
    protected $signature = 'hotswap:remove {name}';
    protected $description = 'Remove an existing module and clean up its references';

    public function handle()
    {
        $name   = $this->argument('name');
        $studly = Str::studly($name);
        $lower  = Str::lower($name);

        $modulePath = base_path("packages/{$lower}");

        // 1️⃣ Remove the module directory
        if (File::exists($modulePath)) {
            File::deleteDirectory($modulePath);
            $this->info("🗑️ Deleted module directory: {$modulePath}");
        } else {
            $this->warn("⚠️ Module '{$lower}' not found in packages/");
        }

        // 2️⃣ Remove from bootstrap/providers.php
        $providersFile = base_path('bootstrap/providers.php');
        if (File::exists($providersFile)) {
            $contents = File::get($providersFile);

            // Match "Ecommerce\App\Providers\AppServiceProvider::class,"
            $pattern = "/.*{$studly}\\\\App\\\\Providers\\\\AppServiceProvider::class,.*\n/";

            $newContents = preg_replace($pattern, '', $contents);

            File::put($providersFile, $newContents);

            $this->info("✅ Removed {$studly}\\App\\Providers\\AppServiceProvider from providers.php");
        }

        // 3️⃣ Remove from composer.json autoload + extra providers
        $composerFile = base_path('composer.json');
        if (File::exists($composerFile)) {
            $composer = json_decode(File::get($composerFile), true);

            // Remove PSR-4 autoload namespace (App)
            $appNamespace = "{$studly}\\App\\";
            if (isset($composer['autoload']['psr-4'][$appNamespace])) {
                unset($composer['autoload']['psr-4'][$appNamespace]);
                $this->info("✅ Removed PSR-4 autoload entry for {$appNamespace}");
            }

            // Remove PSR-4 autoload namespace (Seeders)
            $seedersNamespace = "{$studly}\\Seeders\\";
            if (isset($composer['autoload']['psr-4'][$seedersNamespace])) {
                unset($composer['autoload']['psr-4'][$seedersNamespace]);
                $this->info("✅ Removed PSR-4 autoload entry for {$seedersNamespace}");
            }

            // Remove provider from extra.laravel.providers
            if (isset($composer['extra']['laravel']['providers'])) {
                $providerClass = "Packages\\{$studly}\\Src\\App\\Providers\\AppServiceProvider";
                $composer['extra']['laravel']['providers'] = array_values(array_filter(
                    $composer['extra']['laravel']['providers'],
                    fn($p) => $p !== $providerClass
                ));
                $this->info("✅ Removed {$providerClass} from composer.json providers");
            }

            // Write updated composer.json
            File::put(
                $composerFile,
                json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
            );

            // Regenerate autoload files
            exec('composer dump-autoload');
            $this->info("🔹 Composer autoload regenerated");
        }

        // 4️⃣ Remove from vite.config.ts
        $viteFile = base_path('vite.config.ts');

        if (File::exists($viteFile)) {
            $viteContents = File::get($viteFile);
            // Remove entry like: 'packages/ecommerce/src/resources/js/app.tsx',
            $viteContents = preg_replace(
                "/'packages\/{$lower}\/src\/resources\/js\/app\.tsx',?\s*/",
                '',
                $viteContents
            );
            File::put($viteFile, $viteContents);
            $this->info("✅ Removed {$lower} entry from vite.config.ts");
        }

        $this->removePackageSeederCall($studly, $lower);

        $this->info("🎉 Module '{$studly}' has been fully removed!");
        $this->warn("⚠️ Run `composer dump-autoload` to refresh autoload files.");
        return 0;
    }

    protected function removePackageSeederCall(string $studly, string $lower): void
    {
        $seederFile = base_path('database/seeders/DatabaseSeeder.php');

        if (!file_exists($seederFile)) {
            $this->warn("Root DatabaseSeeder.php not found, skipping removing package seeder call.");
            return;
        }

        $contents = file_get_contents($seederFile);

        // The line to remove
        $callLine = "        \$this->call(\\{$studly}\\Seeders\\DatabaseSeeder::class);";

        if (!str_contains($contents, $callLine)) {
            $this->info("Seeder call for {$studly} not found in DatabaseSeeder.php, nothing to remove.");
            return;
        }

        // Remove the line
        $contents = str_replace($callLine . "\n", '', $contents);

        file_put_contents($seederFile, $contents);

        $this->info("✅ Removed {$studly} package seeder call from DatabaseSeeder.php");
    }
}
