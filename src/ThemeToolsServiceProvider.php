<?php

declare(strict_types=1);

namespace Dccp\ThemeTools;

use Dccp\ThemeTools\Console\Commands\AddThemeCommand;
use Dccp\ThemeTools\Console\Commands\ImportThemeCommand;
use Dccp\ThemeTools\Console\Commands\RemoveThemeCommand;
use Dccp\ThemeTools\Console\Commands\ThemeSetupCommand;
use Dccp\ThemeTools\Console\Commands\ThemeUpdateCommand;
use Illuminate\Support\ServiceProvider;

final class ThemeToolsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            AddThemeCommand::class,
            ImportThemeCommand::class,
            RemoveThemeCommand::class,
            ThemeSetupCommand::class,
            ThemeUpdateCommand::class,
        ]);
    }
}
