<?php

declare(strict_types=1);

namespace Dccp\ThemeTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ThemeUpdateCommand extends Command
{
    protected $signature = 'theme:update {--force : Force update without confirmation}';

    protected $description = 'Update the theme personalization components';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will overwrite your theme personalization components. Do you wish to continue?')) {
            $this->info('Update cancelled.');

            return 0;
        }

        $stack = $this->detectStack();

        if ($stack === 'react') {
            $stubPath = __DIR__.'/../../../stubs/react/resources/js/components/theme-switcher.tsx';
            $destPath = resource_path('js/components/theme-switcher.tsx');
        } else {
            $stubPath = __DIR__.'/../../../stubs/vue/resources/js/components/ThemeSwitcher.vue';
            $destPath = resource_path('js/components/ThemeSwitcher.vue');
        }

        if (! File::exists($stubPath)) {
            $this->error('Theme stub not found at: '.$stubPath);

            return 1;
        }

        File::ensureDirectoryExists(dirname($destPath));
        File::copy($stubPath, $destPath);
        $this->info('Updated '.basename($destPath));

        return 0;
    }

    private function detectStack(): string
    {
        if (File::exists(resource_path('js/components/theme-switcher.tsx'))) {
            return 'react';
        }

        if (File::exists(resource_path('js/components/ThemeSwitcher.vue'))) {
            return 'vue';
        }

        if (File::exists(resource_path('js/app.tsx'))) {
            return 'react';
        }

        return 'vue';
    }
}
