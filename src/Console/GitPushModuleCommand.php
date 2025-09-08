<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class GitPushModuleCommand extends Command
{
    protected $signature = 'hotswap:push {name} {--m= : Commit message}';
    protected $description = 'Push a module in packages/{name} to its Git repository';

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

        $message = $this->option('m') ?? "Update {$studly} module";

        // 1️⃣ git add .
        $this->runProcess(['git', 'add', '.'], $modulePath);

        // 2️⃣ git commit
        $this->runProcess(['git', 'commit', '-m', $message], $modulePath);

        // 3️⃣ git push
        $this->runProcess(['git', 'push'], $modulePath);

        $this->info("✅ {$studly} pushed successfully.");
        return 0;
    }

    protected function runProcess(array $command, string $workingDir): void
    {
        $process = new Process($command, $workingDir);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("❌ Command failed: " . implode(' ', $command));
        }
    }
}
