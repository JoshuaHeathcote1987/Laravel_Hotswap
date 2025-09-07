<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class RemoveModuleCommand extends Command
{
    protected $signature = 'hotswap:remove {name}';
    protected $description = 'Remove a module';

    public function handle()
    {
        $this->info('hotswap:remove called');
    }
}
