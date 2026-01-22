<?php

declare(strict_types=1);

namespace Dccp\ThemeTools\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class AddThemeCommand extends Command
{
    protected $signature = 'theme:add {url : The URL of the shadcn theme JSON}';

    protected $description = 'Download and install a shadcn theme from a JSON definition';

    public function handle(): ?int
    {
        $url = (string) $this->argument('url');

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL provided.');

            return 1;
        }

        $this->info(sprintf('Fetching theme from %s...', $url));

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                $this->error('Failed to download theme definition.');

                return 1;
            }

            $data = $response->json();

            if (! is_array($data) || ! isset($data['cssVars'])) {
                $this->error('Invalid theme JSON structure.');

                return 1;
            }

            $themeName = (string) ($data['name'] ?? $this->extractThemeNameFromUrl($url));
            $themeId = Str::slug($themeName, '-');
            $displayName = Str::title(str_replace('-', ' ', $themeName));

            $this->info(sprintf('Processing theme: %s (%s)', $displayName, $themeId));

            $fontImports = $this->buildFontImports($data['cssVars']);
            $cssContent = $this->generateCss($themeId, $data['cssVars']);

            $cssPath = resource_path(sprintf('css/themes/%s.css', $themeId));
            File::ensureDirectoryExists(dirname($cssPath));
            File::put($cssPath, $cssContent);
            $this->info('Created CSS file: '.$cssPath);

            $this->updateAppCss($themeId, $fontImports);

            $this->updateThemesTs($themeId, $displayName, $data['cssVars']);

            $this->info(sprintf("Theme '%s' installed successfully!", $displayName));
        } catch (Exception $exception) {
            $this->error('Error: '.$exception->getMessage());

            return 1;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    protected function generateCss(string $themeId, array $vars): string
    {
        $light = $vars['light'] ?? [];
        $dark = $vars['dark'] ?? [];
        $common = $vars['theme'] ?? [];

        $css = '';

        $css .= ":root.theme-{$themeId},\n";
        $css .= ".theme-{$themeId} {\n";

        foreach ($common as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }

        foreach ($light as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }

        $css .= "}\n\n";

        $css .= ":root.dark.theme-{$themeId},\n";
        $css .= ".dark.theme-{$themeId} {\n";
        foreach ($dark as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }

        return $css."}\n";
    }

    /**
     * @param  array<int, string>  $fontImports
     */
    protected function updateAppCss(string $themeId, array $fontImports = []): void
    {
        $appCssPath = resource_path('css/app.css');
        $content = File::get($appCssPath);
        $updated = $content;

        foreach (array_unique($fontImports) as $import) {
            if (Str::contains($updated, $import)) {
                continue;
            }

            $updated = $this->insertFontImport($updated, $import);
        }

        $importLine = sprintf("@import './themes/%s.css';", $themeId);
        $importPattern = '/@import\s+["\']\.\/themes\/'.preg_quote($themeId, '/').'\.css["\'];/';

        if (! preg_match($importPattern, $updated)) {
            $themeImportPattern = '/@import\s+["\']\.\/themes\/[^"\']+\.css["\'];/';

            if (preg_match_all($themeImportPattern, $updated, $matches, PREG_OFFSET_CAPTURE)) {
                $lastMatch = end($matches[0]);
                $insertPosition = $lastMatch[1] + mb_strlen($lastMatch[0]);
                $updated = mb_substr($updated, 0, $insertPosition).(PHP_EOL.$importLine).mb_substr($updated, $insertPosition);
            } else {
                $updated = str_replace('@source', $importLine.'

@source', $updated);
            }
        }

        if ($updated !== $content) {
            File::put($appCssPath, $updated);
            $this->info('Updated app.css');
        } else {
            $this->warn('app.css already contains the import.');
        }
    }

    /**
     * @param  array<string, mixed>  $vars
     * @return array<int, string>
     */
    protected function buildFontImports(array $vars): array
    {
        $common = $vars['theme'] ?? [];
        $light = $vars['light'] ?? [];
        $fonts = ['font-sans', 'font-serif', 'font-mono'];
        $imports = [];

        foreach ($fonts as $fontKey) {
            $fontValue = $common[$fontKey] ?? $light[$fontKey] ?? null;

            if (! $fontValue || ! is_string($fontValue)) {
                continue;
            }

            if (Str::startsWith($fontValue, 'var(')) {
                continue;
            }

            $fontFamily = mb_trim(explode(',', $fontValue)[0]);
            $fontFamily = mb_trim($fontFamily, "'\"");
            $ignored = ['ui-sans-serif', 'system-ui', 'sans-serif', 'serif', 'monospace', 'inherit'];

            if (in_array(mb_strtolower($fontFamily), $ignored)) {
                continue;
            }

            $encodedFamily = urlencode($fontFamily);
            $imports[] = sprintf("@import url('https://fonts.googleapis.com/css2?family=%s&display=swap');", $encodedFamily);
        }

        return $imports;
    }

    protected function insertFontImport(string $content, string $import): string
    {
        $pattern = '/@import\s+["\']tailwindcss["\'];/';
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0];

            return mb_substr($content, 0, $match[1]).($import.'

').mb_substr($content, $match[1]);
        }

        $pattern = '/@import\s+["\']tw-animate-css["\'];/';
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0];

            return mb_substr($content, 0, $match[1]).($import.'

').mb_substr($content, $match[1]);
        }

        return $import.PHP_EOL.$content;
    }

    protected function extractThemeNameFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $filename = basename((string) $path, '.json');

        return Str::title(str_replace(['-', '_'], ' ', $filename));
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    protected function updateThemesTs(string $themeId, string $displayName, array $vars): void
    {
        $tsPath = resource_path('js/conf/themes.ts');
        $content = File::get($tsPath);

        $colorThemePattern = '/export type ColorTheme =\s*\n([\s\S]*?);/';
        if (preg_match($colorThemePattern, $content, $matches)) {
            $existingTypes = trim($matches[1]);
            $hasType = Str::contains($existingTypes, sprintf("'%s'", $themeId))
                || Str::contains($existingTypes, sprintf('"%s"', $themeId));

            if (! $hasType) {
                $newTypes = "{$existingTypes}\n    | '{$themeId}'";
                $content = preg_replace($colorThemePattern, "export type ColorTheme =\n{$newTypes};", $content);
            }
        }

        $light = $vars['light'] ?? [];
        $primary = $light['primary'] ?? 'oklch(0.5 0.2 250)';
        $secondary = $light['secondary'] ?? 'oklch(0.9 0.05 250)';
        $accent = $light['accent'] ?? 'oklch(0.9 0.05 250)';

        $fontValue = (string) ($vars['theme']['font-sans'] ?? $light['font-sans'] ?? 'System Sans');
        $fontName = mb_trim(explode(',', $fontValue)[0]);
        $fontName = mb_trim($fontName, "'\"");

        $newThemeConfig = "    {\n";
        $newThemeConfig .= "        id: '{$themeId}',\n";
        $newThemeConfig .= "        name: '{$displayName}',\n";
        $newThemeConfig .= "        description: 'Imported from a shadcn theme registry.',\n";
        $newThemeConfig .= "        font: '{$fontName}',\n";
        $newThemeConfig .= "        colors: {\n";
        $newThemeConfig .= "            primary: '{$primary}',\n";
        $newThemeConfig .= "            secondary: '{$secondary}',\n";
        $newThemeConfig .= "            accent: '{$accent}',\n";
        $newThemeConfig .= "        },\n";
        $newThemeConfig .= '    }';

        $themeConfigPattern = '/id:\s*["\']'.preg_quote($themeId, '/').'["\']/';

        if (preg_match($themeConfigPattern, $content)) {
            $this->warn('themes.ts already contains this theme.');

            return;
        }

        // Remove the closing bracket and any trailing comma/whitespace before it
        $contentWithoutEnd = preg_replace('/,?\s*\n\];\s*$/', '', $content);

        if ($contentWithoutEnd === null || $contentWithoutEnd === $content) {
            // Fallback if regex failed to match expected end structure
            $this->error('Unable to parse themes.ts structure.');

            return;
        }

        $updatedContent = $contentWithoutEnd.",\n{$newThemeConfig}\n];";

        File::put($tsPath, $updatedContent);
        $this->info('Updated themes.ts');
    }
}
