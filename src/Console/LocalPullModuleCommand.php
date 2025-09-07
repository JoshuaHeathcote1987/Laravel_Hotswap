<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class LocalPullModuleCommand extends Command
{
    protected $signature = 'hotswap:localpull {name}';
    protected $description = 'Pull a module from a local repository';

    public function handle()
    {
        $this->info('hotswap:localpull called');
    }
}
