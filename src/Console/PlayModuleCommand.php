<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class PlayModuleCommand extends Command
{
    protected $signature = 'hotswap:play {name}';
    protected $description = 'Create a new repository';

    public function handle()
    {
        $this->info('hotswap:play called');
    }
}