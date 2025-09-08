<?php

namespace JoshLogic\Hotswap;

use Illuminate\Support\ServiceProvider;
use JoshLogic\Hotswap\Console\{
    CreateModuleCommand,
    RemoveModuleCommand,
    PauseModuleCommand,
    PlayModuleCommand,
    GitPushModuleCommand,
    GitPullModuleCommand,
    ScaffoldHotswapCommand
};

class HotswapServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            CreateModuleCommand::class,
            RemoveModuleCommand::class,
            PauseModuleCommand::class,
            PlayModuleCommand::class,
            GitPushModuleCommand::class,
            GitPullModuleCommand::class,
            ScaffoldHotswapCommand::class
        ]);
    }

    public function boot()
    {
        // Publish core_struct files to the Laravel root
        $this->publishes([
            __DIR__ . '/core_struct/composer.json' => base_path('composer.json'),
            __DIR__ . '/core_struct/vite.config.ts' => base_path('vite.config.ts'),
        ], 'hotswap-core');
    }
}
