<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateModuleCommand extends Command
{
    protected $signature = 'hotswap:create {name}';
    protected $description = 'Create a new module inside packages/ and register it with Laravel';

    public function handle()
    {
        $name   = $this->argument('name');
        $studly = Str::studly($name);   // Ecommerce
        $lower  = Str::lower($name);    // ecommerce

        $rootPath   = base_path('packages');
        $modulePath = $rootPath . '/' . $lower;

        // Ensure packages/ exists
        if (!File::exists($rootPath)) {
            File::makeDirectory($rootPath, 0755, true);
            $this->info("Created packages directory.");
        }

        // Ensure module does not already exist
        if (File::exists($modulePath)) {
            $this->error("❌ Module '{$lower}' already exists!");
            return 1;
        }

        // Copy stub structure
        $stubPath = base_path('vendor/joshlogic/hotswap/src/file_struct');
        if (!File::exists($stubPath)) {
            $this->error("❌ Could not find file_struct at: {$stubPath}");
            return 1;
        }

        File::copyDirectory($stubPath, $modulePath);

        // Update seeder namespaces
        $this->updateSeederNamespace($lower, $studly);

        // Update factoriy namespaces
        $this->updateComposerFactories($studly, $lower);

        // Replace placeholders and rename things
        $this->replaceInPhpFiles($modulePath, $studly, $lower);
        $this->renameFiles($modulePath, $studly, $lower);
        $this->renameDirectoriesDeepestFirst($modulePath, $studly, $lower);

        // 🔹 If HOTSWAP_ENV=vue, override index.tsx with Vue file
        if (env('HOTSWAP_ENV') === 'vue') {
            $this->swapIndexForVue($lower, $modulePath);
        }

        // Register module in Laravel config files
        $this->updateProvidersPhp($studly);
        $this->updateComposerJson($studly, $lower);
        $this->updateViteConfig($lower);
        $this->addPackageSeederCall($studly, $lower);

        $this->info("✅ Module '{$studly}' created at {$modulePath}");
        return 0;
    }

    protected function replaceInPhpFiles(string $basePath, string $studly, string $lower): void
    {
        foreach (File::allFiles($basePath) as $file) {
            if (Str::endsWith($file->getFilename(), '.php')) {
                $contents = File::get($file->getRealPath());
                $contents = str_replace('Placeholder', $studly, $contents);
                $contents = str_replace('placeholder', $lower, $contents);
                File::put($file->getRealPath(), $contents);
            }
        }
    }

    protected function renameFiles(string $basePath, string $studly, string $lower): void
    {
        foreach (File::allFiles($basePath) as $file) {
            $oldName = $file->getFilename();
            $newName = str_replace(['Placeholder', 'placeholder'], [$studly, $lower], $oldName);

            if ($newName !== $oldName) {
                $newPath = $file->getPath() . DIRECTORY_SEPARATOR . $newName;
                if (!File::exists($newPath)) {
                    File::move($file->getRealPath(), $newPath);
                }
            }
        }
    }

    protected function renameDirectoriesDeepestFirst(string $basePath, string $studly, string $lower): void
    {
        $dirs = $this->allDirectoriesRecursive($basePath);
        usort($dirs, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($dirs as $dir) {
            $base = basename($dir);
            $newBase = str_replace(['Placeholder', 'placeholder'], [$studly, $lower], $base);

            if ($newBase !== $base) {
                $newPath = dirname($dir) . DIRECTORY_SEPARATOR . $newBase;
                if (!File::exists($newPath)) {
                    File::move($dir, $newPath);
                }
            }
        }
    }

    protected function allDirectoriesRecursive(string $path): array
    {
        $result = [];
        foreach (File::directories($path) as $dir) {
            $result[] = $dir;
            $result = array_merge($result, $this->allDirectoriesRecursive($dir));
        }
        return $result;
    }

    protected function updateProvidersPhp(string $studly): void
    {
        $file = base_path('bootstrap/providers.php');
        $line = "    {$studly}\\App\\Providers\\AppServiceProvider::class,";

        $contents = file_get_contents($file);

        if (strpos($contents, $line) === false) {
            $contents = str_replace("];", "{$line}\n];", $contents);
            file_put_contents($file, $contents);
            $this->line("🔹 Added {$studly} provider to bootstrap/providers.php");
        }
    }

    protected function updateComposerJson(string $studly, string $lower): void
    {
        $file = base_path('composer.json');
        $json = json_decode(file_get_contents($file), true);

        // 🔹 Add App namespace
        $appNamespace = "{$studly}\\App\\";
        $appPath = "packages/{$lower}/src/App/";
        if (!isset($json['autoload']['psr-4'][$appNamespace])) {
            $json['autoload']['psr-4'][$appNamespace] = $appPath;
            $this->line("🔹 Added PSR-4 autoload for {$studly}\\App to composer.json");
        }

        // 🔹 Add Seeders namespace
        $seedersNamespace = "{$studly}\\Seeders\\";
        $seedersPath = "packages/{$lower}/src/databases/seeders/";
        if (!isset($json['autoload']['psr-4'][$seedersNamespace])) {
            $json['autoload']['psr-4'][$seedersNamespace] = $seedersPath;
            $this->line("🔹 Added PSR-4 autoload for {$studly}\\Seeders to composer.json");
        }

        // 🔹 Add provider
        $providerClass = "Packages\\{$studly}\\Src\\App\\Providers\\AppServiceProvider";
        if (!in_array($providerClass, $json['extra']['laravel']['providers'] ?? [])) {
            $json['extra']['laravel']['providers'][] = $providerClass;
            $this->line("🔹 Added provider {$providerClass} to composer.json");
        }

        file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 🔹 Regenerate Composer autoload
        exec('composer dump-autoload');
    }

    protected function updateViteConfig(string $lower): void
    {
        $file = base_path('vite.config.ts');
        if (!File::exists($file)) {
            $this->warn("vite.config.ts not found, skipping update.");
            return;
        }

        $contents = File::get($file);

        $inputLine = "                'packages/{$lower}/src/resources/js/app.tsx'";
        $aliasLine = "            '@{$lower}': path.resolve(__dirname, 'packages/{$lower}/src/resources/js')";

        // Add module to laravel input array
        if (strpos($contents, $inputLine) === false) {
            $contents = preg_replace_callback(
                '/laravel\s*\(\s*\{\s*input\s*:\s*\[([\s\S]*?)\]/m',
                function ($matches) use ($inputLine) {
                    $block = trim($matches[1]);

                    // Split into lines and normalize commas
                    $lines = array_filter(array_map('trim', explode("\n", $block)));

                    if (!empty($lines)) {
                        $lastIndex = count($lines) - 1;
                        $lines[$lastIndex] = rtrim($lines[$lastIndex], ',');
                        $lines[$lastIndex] .= ','; // ensure exactly one comma
                    }

                    $lines[] = $inputLine . ","; // add our new line

                    $block = "    " . implode("\n    ", $lines);
                    return "laravel({\n    input: [\n{$block}\n    ]";
                },
                $contents
            );
            $this->line("🔹 Added '{$lower}' input line to Vite laravel input");
        }

        // Add alias for module
        if (strpos($contents, $aliasLine) === false) {
            $contents = preg_replace_callback(
                '/alias\s*:\s*\{([\s\S]*?)\}/m',
                function ($matches) use ($aliasLine) {
                    $block = trim($matches[1]);

                    // Split into lines and normalize commas
                    $lines = array_filter(array_map('trim', explode("\n", $block)));

                    if (!empty($lines)) {
                        $lastIndex = count($lines) - 1;
                        $lines[$lastIndex] = rtrim($lines[$lastIndex], ',');
                        $lines[$lastIndex] .= ',';
                    }

                    $lines[] = $aliasLine . ",";

                    $block = "    " . implode("\n    ", $lines);
                    return "alias: {\n{$block}\n}";
                },
                $contents
            );
            $this->line("🔹 Added alias '@{$lower}' to Vite config");
        }

        File::put($file, $contents);
    }

    protected function swapIndexForVue(string $lower, string $modulePath): void
    {
        $vueSource = base_path('vendor/joshlogic/hotswap/src/vue_files/index.vue');
        $targetDir = "{$modulePath}/src/resources/js/pages/{$lower}";

        if (!File::exists($vueSource)) {
            $this->error("❌ Vue stub file not found at: {$vueSource}");
            return;
        }

        // Ensure target dir exists
        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/index.vue';
        File::copy($vueSource, $targetFile);

        // Delete the old index.tsx if it exists
        $oldFile = $targetDir . '/index.tsx';
        if (File::exists($oldFile)) {
            File::delete($oldFile);
        }

        $this->line("🔹 Vue mode enabled — swapped index.tsx with index.vue");
    }

    protected function updateSeederNamespace(string $package, string $studlyPackage): void
    {
        $seederPath = base_path("packages/{$package}/src/databases/seeders");

        if (!is_dir($seederPath)) {
            return; // nothing to update
        }

        $files = glob($seederPath . '/*.php');

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            // Replace the namespace
            $contents = preg_replace(
                '/^namespace\s+Database\\\\Seeders\s*;/m',
                "namespace {$studlyPackage}\\Seeders;",
                $contents
            );

            file_put_contents($file, $contents);
            $this->info("🔹 Updated namespace in " . basename($file));
        }
    }

    protected function addPackageSeederCall(string $studly, string $lower): void
    {
        $seederFile = base_path('database/seeders/DatabaseSeeder.php');

        if (!file_exists($seederFile)) {
            $this->warn("Root DatabaseSeeder.php not found, skipping adding package seeder call.");
            return;
        }

        $contents = file_get_contents($seederFile);

        // The line to insert
        $callLine = "        \$this->call(\\{$studly}\\Seeders\\DatabaseSeeder::class);";

        // Check if it already exists to avoid duplicates
        if (str_contains($contents, $callLine)) {
            $this->info("✅ Seeder call for {$studly} already exists in DatabaseSeeder.php");
            return;
        }

        // Insert before the last closing bracket of the run() method
        $contents = preg_replace(
            '/(\s*}\s*)$/m',
            "    {$callLine}\n$1",
            $contents,
            1
        );

        file_put_contents($seederFile, $contents);

        $this->info("✅ Added {$studly} package seeder call to DatabaseSeeder.php");
    }

    protected function updateComposerFactories(string $studly, string $lower): void
    {
        $file = base_path('composer.json');
        $json = json_decode(file_get_contents($file), true);

        // Key & value we want to add
        $autoloadKey = "{$studly}\\Factories\\";
        $autoloadVal = "packages/{$lower}/src/databases/factories/";

        // Add only if it doesn't already exist
        if (!isset($json['autoload']['psr-4'][$autoloadKey])) {
            $json['autoload']['psr-4'][$autoloadKey] = $autoloadVal;
            $this->line("🔹 Added PSR-4 autoload for {$studly}\\Factories to composer.json");
        }

        // Save changes back to composer.json
        file_put_contents(
            $file,
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }
}
