<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class LocalPushModuleCommand extends Command
{
    protected $signature = 'hotswap:localpush {name}';
    protected $description = 'Push a module to a local repository';

    public function handle()
    {
        $this->info('hotswap:localpush called');
    }
}
