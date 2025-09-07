<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;

class HelloWorldCommand extends Command
{
    protected $signature = 'hotswap:hello';
    protected $description = 'Outputs Hello World to the terminal';

    public function handle()
    {
        $this->info('Hello World');
    }
}