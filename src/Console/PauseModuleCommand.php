<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class PauseModuleCommand extends Command
{
    protected $signature = 'hotswap:pause {name}';
    protected $description = 'Pause a module (prevent it from displaying)';

    public function handle()
    {
        $this->info('hotswap:pause called');
    }
}
