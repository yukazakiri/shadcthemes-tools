<?php

declare(strict_types=1);

namespace Dccp\ThemeTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

final class ThemeUpdateCommand extends Command
{
    protected $signature = 'theme:update {--force : Force update without confirmation}';

    protected $description = 'Update the theme personalization components';

    public function handle(): int
    {
        $stack = $this->detectStack();

        if ($stack === null) {
            $stack = select(
                label: 'Which stack are you using?',
                options: [
                    'react' => 'Inertia React',
                    'vue' => 'Inertia Vue',
                ],
                default: 'react',
                hint: 'We couldn\'t auto-detect your stack'
            );
        }

        if (! $this->option('force') && ! confirm('This will overwrite your theme personalization components. Do you wish to continue?', false)) {
            info('Update cancelled.');

            return 0;
        }

        if ($stack === 'react') {
            $stubPath = __DIR__.'/../../../stubs/react/resources/js/components/theme-switcher.tsx';
            $destPath = resource_path('js/components/theme-switcher.tsx');
        } else {
            $stubPath = __DIR__.'/../../../stubs/vue/resources/js/components/ThemeSwitcher.vue';
            $destPath = resource_path('js/components/ThemeSwitcher.vue');
        }

        if (! File::exists($stubPath)) {
            error('Theme stub not found at: '.$stubPath);

            return 1;
        }

        File::ensureDirectoryExists(dirname($destPath));
        File::copy($stubPath, $destPath);
        info('  ✓ Updated '.basename($destPath));

        note('');
        info('Theme components updated successfully!');
        note('Run <comment>npm run build</comment> or <comment>npm run dev</comment> to apply the changes.');

        return 0;
    }

    private function detectStack(): ?string
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

        if (File::exists(resource_path('js/app.ts')) && File::exists(resource_path('js/Pages'))) {
            return 'vue';
        }

        return null;
    }
}
