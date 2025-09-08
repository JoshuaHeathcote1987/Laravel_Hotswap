<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class GitPullModuleCommand extends Command
{
    protected $signature = 'hotswap:push {name}';
    protected $description = 'Pull a module from its Git repository and update root files';

    public function handle()
    {
        $name = $this->argument('name');
        $lower = strtolower($name);
        $studly = ucfirst($name);

        $modulePath = base_path("packages/{$lower}");

        if (!File::exists($modulePath)) {
            $this->error("❌ Module '{$lower}' not found in packages/");
            return 1;
        }

        if (!File::exists($modulePath . '/.git')) {
            $this->error("❌ No git repo found in {$modulePath}. Did you clone it?");
            return 1;
        }

        // 1️⃣ Pull latest changes
        $this->line("📥 Pulling latest changes for {$studly}...");
        $process = new Process(['git', 'pull'], $modulePath);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error("❌ Git pull failed for {$studly}.");
            return 1;
        }

        $this->info("✅ Git pull completed for {$studly}.");

        // 2️⃣ Update root files
        $this->updateProviders($studly, $lower);
        $this->updateComposer($studly, $lower);
        $this->updateViteConfig($studly, $lower);

        $this->info("🎉 {$studly} synced successfully.");
        return 0;
    }

    protected function updateProviders(string $studly, string $lower): void
    {
        $file = base_path('bootstrap/providers.php');
        if (!File::exists($file)) {
            $this->warn("⚠️ bootstrap/providers.php not found.");
            return;
        }

        $contents = File::get($file);
        $provider = "Packages\\" . $studly . "\\App\\Providers\\" . $studly . "ServiceProvider::class,";

        if (!str_contains($contents, $provider)) {
            $contents = preg_replace(
                '/return\s*\[(.*)\];/s',
                "return [\n    $provider\n$1];",
                $contents
            );
            File::put($file, $contents);
            $this->line("🔹 Added {$studly} provider to bootstrap/providers.php");
        }
    }

    protected function updateComposer(string $studly, string $lower): void
    {
        $file = base_path('composer.json');
        if (!File::exists($file)) {
            $this->warn("⚠️ composer.json not found.");
            return;
        }

        $composer = json_decode(File::get($file), true);

        // Add path repo if missing
        $repo = [
            'type' => 'path',
            'url' => "packages/{$lower}"
        ];
        $exists = collect($composer['repositories'] ?? [])->contains(fn($r) => $r['url'] === $repo['url']);
        if (!$exists) {
            $composer['repositories'][] = $repo;
            $this->line("🔹 Added path repo for {$studly}");
        }

        // Add require entry
        $packageName = "packages/{$lower}";
        if (!isset($composer['require'][$packageName])) {
            $composer['require'][$packageName] = '*';
            $this->line("🔹 Added require entry for {$studly}");
        }

        File::put($file, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function updateViteConfig(string $studly, string $lower): void
    {
        $file = base_path('vite.config.ts');
        if (!File::exists($file)) {
            $this->warn("⚠️ vite.config.ts not found.");
            return;
        }

        $contents = File::get($file);
        $entry = "'packages/{$lower}/src/resources/js/app.tsx'";

        if (!str_contains($contents, $entry)) {
            $contents = preg_replace(
                '/input:\s*\[(.*?)\]/s',
                "input: [\n        $1,\n        {$entry}\n    ]",
                $contents
            );
            File::put($file, $contents);
            $this->line("🔹 Added {$studly} to vite.config.ts");
        }
    }
}
