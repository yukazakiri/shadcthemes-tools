<?php

declare(strict_types=1);

namespace Dccp\ThemeTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class RemoveThemeCommand extends Command
{
    protected $signature = 'theme:remove
                            {theme? : The theme ID to remove (e.g. catppuccin)}
                            {--list : List all available themes}
                            {--force : Remove without confirmation}';

    protected $description = 'Remove a theme from the application';

    private string $themesDir;

    private string $themesConfigPath;

    private string $appCssPath;

    /** @var array<string> */
    private array $protectedThemes = ['default'];

    public function handle(): int
    {
        $this->themesDir = resource_path('css/themes');
        $this->themesConfigPath = resource_path('js/conf/themes.ts');
        $this->appCssPath = resource_path('css/app.css');

        if ($this->option('list')) {
            return $this->listThemes();
        }

        $themeId = $this->argument('theme');

        if (! $themeId) {
            $themes = $this->getAvailableThemes();

            if ($themes === []) {
                $this->error('No themes available to remove.');

                return self::FAILURE;
            }

            $themeId = $this->choice(
                'Which theme would you like to remove?',
                $themes,
                0
            );
        }

        if (in_array($themeId, $this->protectedThemes)) {
            $this->error(sprintf("The '%s' theme is protected and cannot be removed.", $themeId));

            return self::FAILURE;
        }

        if (! $this->themeExists($themeId)) {
            $this->error(sprintf("Theme '%s' does not exist.", $themeId));

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf("Are you sure you want to remove the '%s' theme?", $themeId))) {
            $this->info('Removal cancelled.');

            return self::SUCCESS;
        }

        $this->info('Removing theme: ' . $themeId);

        $this->removeCssFile($themeId);
        $this->removeAppCssImport($themeId);
        $this->removeThemesConfig($themeId);

        $this->newLine();
        $this->info('Theme removed successfully!');
        $this->newLine();
        $this->line('Run <comment>npm run build</comment> or <comment>npm run dev</comment> to apply the changes.');

        return self::SUCCESS;
    }

    private function listThemes(): int
    {
        $themes = $this->getAvailableThemes();

        if ($themes === []) {
            $this->info('No custom themes installed.');

            return self::SUCCESS;
        }

        $this->info('Available themes:');
        $this->newLine();

        foreach ($themes as $theme) {
            $protected = in_array($theme, $this->protectedThemes) ? ' <comment>(protected)</comment>' : '';
            $this->line(sprintf('  - %s%s', $theme, $protected));
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function getAvailableThemes(): array
    {
        if (! File::isDirectory($this->themesDir)) {
            return [];
        }

        $files = File::files($this->themesDir);
        $themes = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'css') {
                $themes[] = $file->getFilenameWithoutExtension();
            }
        }

        if (! in_array('default', $themes)) {
            array_unshift($themes, 'default');
        }

        sort($themes);

        return $themes;
    }

    private function themeExists(string $themeId): bool
    {
        if ($themeId === 'default') {
            return true;
        }

        $cssFile = sprintf('%s/%s.css', $this->themesDir, $themeId);

        return File::exists($cssFile);
    }

    private function removeCssFile(string $themeId): void
    {
        $cssFile = sprintf('%s/%s.css', $this->themesDir, $themeId);

        if (File::exists($cssFile)) {
            File::delete($cssFile);
            $this->info('Removed CSS file: ' . $cssFile);
        } else {
            $this->line('CSS file not found: ' . $cssFile);
        }
    }

    private function removeAppCssImport(string $themeId): void
    {
        $appCss = File::get($this->appCssPath);

        $patterns = [
            '/@import\s+["\']\.\/themes\/'.$themeId.'\.css["\'];\n?/',
            '/@import\s+["\']\.\.\/css\/themes\/'.$themeId.'\.css["\'];\n?/',
        ];

        $modified = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, (string) $appCss)) {
                $appCss = preg_replace($pattern, '', (string) $appCss);
                $modified = true;
            }
        }

        if ($modified) {
            $appCss = preg_replace('/\n{3,}/', "\n\n", (string) $appCss);
            File::put($this->appCssPath, $appCss);
            $this->info('Removed import from app.css');
        } else {
            $this->line('Import not found in app.css');
        }
    }

    private function removeThemesConfig(string $themeId): void
    {
        $themesConfig = File::get($this->themesConfigPath);
        $modified = false;

        $colorThemePattern = '/export type ColorTheme =\s*\n([\s\S]*?);/';
        if (preg_match($colorThemePattern, $themesConfig, $matches)) {
            $typeLines = explode("\n", trim($matches[1]));
            $filteredLines = [];

            foreach ($typeLines as $line) {
                if (str_contains($line, sprintf("'%s'", $themeId)) || str_contains($line, sprintf('"%s"', $themeId))) {
                    $modified = true;

                    continue;
                }

                $filteredLines[] = $line;
            }

            if ($modified) {
                $newTypes = implode("\n", $filteredLines);
                $themesConfig = preg_replace($colorThemePattern, "export type ColorTheme =\n{$newTypes};", $themesConfig);
            }
        }

        $themeObjectPattern = '/\s*,?\n\s*\{[\s\S]*?id:\s*["\']'.preg_quote($themeId, '/').'["\'][\s\S]*?\n\s*\},?/';

        if (preg_match($themeObjectPattern, (string) $themesConfig)) {
            $themesConfig = preg_replace($themeObjectPattern, '', (string) $themesConfig);
            $modified = true;
        }

        $themesConfig = preg_replace('/,\s*,/', ',', (string) $themesConfig);
        $themesConfig = preg_replace('/,\s*\]/', "\n]", (string) $themesConfig);
        $themesConfig = preg_replace('/\[\s*,/', '[', (string) $themesConfig);

        if ($modified) {
            File::put($this->themesConfigPath, $themesConfig);
            $this->info('Removed theme from themes.ts configuration');
        } else {
            $this->line('Theme not found in themes.ts configuration');
        }
    }
}
