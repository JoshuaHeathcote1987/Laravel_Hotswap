<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class MakeLocalRepoCommand extends Command
{
    protected $signature = 'hotswap:mkrepo {name}';
    protected $description = 'Create a new repository';

    public function handle()
    {
        $this->info('hotswap:mkrepo called');
    }
}
