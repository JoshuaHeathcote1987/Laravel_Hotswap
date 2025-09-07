<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class GitPushModuleCommand extends Command
{
    protected $signature = 'hotswap:gitpush {name}';
    protected $description = 'Push a module to a git repository';

    public function handle()
    {
        $this->info('hotswap:gitpush called');
    }
}
