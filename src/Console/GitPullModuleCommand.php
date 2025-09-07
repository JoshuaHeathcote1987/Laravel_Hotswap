<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class GitPullModuleCommand extends Command
{
    protected $signature = 'hotswap:gitpull {name}';
    protected $description = 'Pull a module from a git repository';

    public function handle()
    {
        $this->info('hotswap:gitpull called');
    }
}
