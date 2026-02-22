<?php

declare(strict_types=1);

namespace Dccp\ThemeTools\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

final class ImportThemeCommand extends Command
{
    protected $signature = 'theme:import
                            {url? : The URL to the theme JSON file (e.g. https://tweakcn.com/r/themes/vintage-paper.json)}
                            {--name= : Override the theme name}
                            {--description= : Custom description for the theme}';

    protected $description = 'Import a shadcn theme from a tweakcn.com JSON URL';

    private string $themesDir;

    private string $themesConfigPath;

    private string $appCssPath;

    public function handle(): int
    {
        $this->themesDir = resource_path('css/themes');
        $this->themesConfigPath = resource_path('js/conf/themes.ts');
        $this->appCssPath = resource_path('css/app.css');

        $url = $this->argument('url');

        // If no URL provided, ask for it interactively
        if (! $url) {
            $url = text(
                label: 'Enter the theme JSON URL',
                placeholder: 'https://tweakcn.com/r/themes/vintage-paper.json',
                hint: 'You can find themes at tweakcn.com or use any shadcn-compatible JSON'
            );

            if (blank($url)) {
                error('A URL is required to import a theme.');

                return self::FAILURE;
            }
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            error('Invalid URL provided.');

            return self::FAILURE;
        }

        /** @var array<string, mixed>|null $themeData */
        $themeData = spin(
            message: 'Fetching theme from: '.$url,
            callback: function () use ($url) {
                try {
                    /** @var Response $response */
                    $response = Http::timeout(30)->get($url);

                    if ($response->failed()) {
                        return null;
                    }

                    return $response->json();
                } catch (Exception) {
                    return null;
                }
            }
        );

        if (! $themeData) {
            error('Failed to fetch or parse theme from the provided URL.');

            return self::FAILURE;
        }

        $rawThemeName = $this->option('name') ?? ($themeData['name'] ?? $this->extractThemeNameFromUrl($url));
        $themeId = Str::slug($rawThemeName);
        $themeName = Str::title(str_replace(['-', '_'], ' ', $rawThemeName));

        if ($this->themeExists($themeId)) {
            if (! confirm(sprintf("Theme '%s' already exists. Overwrite?", $themeId), false)) {
                info('Import cancelled.');

                return self::SUCCESS;
            }
        }

        info(sprintf('Importing theme: %s (id: %s)', $themeName, $themeId));

        $cssContent = $this->generateCssFromThemeData($themeData, $themeId);
        $cssFilePath = sprintf('%s/%s.css', $this->themesDir, $themeId);

        File::ensureDirectoryExists($this->themesDir);
        File::put($cssFilePath, $cssContent);
        info('  ✓ Created CSS file: '.$cssFilePath);

        $this->updateAppCssImport($themeId);

        $description = $this->option('description') ?? 'Imported from tweakcn.';
        $this->updateThemesConfig($themeData, $themeId, $themeName, $description);

        note('');
        info('Theme imported successfully!');
        note('Run <comment>npm run build</comment> or <comment>npm run dev</comment> to apply the theme.');

        return self::SUCCESS;
    }

    private function extractThemeNameFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $filename = basename((string) $path, '.json');

        return Str::title(str_replace(['-', '_'], ' ', $filename));
    }

    private function themeExists(string $themeId): bool
    {
        $cssFile = sprintf('%s/%s.css', $this->themesDir, $themeId);

        return File::exists($cssFile);
    }

    /**
     * @param  array<string, mixed>  $themeData
     */
    private function generateCssFromThemeData(array $themeData, string $themeId): string
    {
        $cssVars = $themeData['cssVars'] ?? [];
        $css = $themeData['css'] ?? [];

        $fontImports = $this->extractFontImports($cssVars);
        $lightVars = $this->buildCssVariables($cssVars['light'] ?? [], $cssVars['theme'] ?? []);
        $darkVars = $this->buildCssVariables($cssVars['dark'] ?? [], []);
        $additionalCss = $this->buildAdditionalCss($css, $themeId);

        $output = '';

        if ($fontImports !== '' && $fontImports !== '0') {
            $output .= $fontImports."\n";
        }

        $output .= ":root.theme-{$themeId},\n.theme-{$themeId} {\n";
        $output .= $lightVars;
        $output .= "}\n\n";

        $output .= ":root.dark.theme-{$themeId},\n.dark.theme-{$themeId} {\n";
        $output .= $darkVars;
        $output .= "}\n";

        if ($additionalCss !== '' && $additionalCss !== '0') {
            $output .= "\n".$additionalCss;
        }

        return $output;
    }

    /**
     * @param  array<string, mixed>  $cssVars
     */
    private function extractFontImports(array $cssVars): string
    {
        $fonts = [];

        foreach (['theme', 'light', 'dark'] as $section) {
            if (! isset($cssVars[$section])) {
                continue;
            }

            foreach (['font-sans', 'font-mono', 'font-serif'] as $fontVar) {
                if (isset($cssVars[$section][$fontVar])) {
                    $fontValue = $cssVars[$section][$fontVar];
                    $primaryFont = mb_trim(explode(',', $fontValue)[0]);
                    $primaryFont = mb_trim($primaryFont, "\"'");

                    if (in_array(mb_strtolower($primaryFont), ['serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', 'ui-sans-serif', 'ui-serif', 'ui-monospace'])) {
                        continue;
                    }

                    $fonts[$primaryFont] = true;
                }
            }
        }

        $imports = [];
        foreach (array_keys($fonts) as $font) {
            $encodedFont = urlencode($font);
            $imports[] = sprintf("@import url('https://fonts.googleapis.com/css2?family=%s&display=swap');", $encodedFont);
        }

        return implode("\n", $imports);
    }

    /**
     * @param  array<string, string>  $vars
     * @param  array<string, string>  $themeVars
     */
    private function buildCssVariables(array $vars, array $themeVars = []): string
    {
        $allVars = array_merge($themeVars, $vars);

        $skipVars = ['radius'];

        $output = '';
        foreach ($allVars as $key => $value) {
            if (in_array($key, $skipVars) && isset($themeVars[$key])) {
                continue;
            }

            $output .= "  --{$key}: {$value};\n";
        }

        return $output;
    }

    /**
     * @param  array<string, mixed>  $css
     */
    private function buildAdditionalCss(array $css, string $themeId): string
    {
        $output = '';

        foreach ($css as $layer => $rules) {
            if ($layer === '@layer base') {
                $output .= "@layer base {\n";
                foreach ($rules as $selector => $properties) {
                    $scopedSelector = sprintf('.theme-%s %s', $themeId, $selector);
                    $output .= "  {$scopedSelector} {\n";
                    foreach ($properties as $prop => $value) {
                        $output .= "    {$prop}: {$value};\n";
                    }

                    $output .= "  }\n";
                }

                $output .= "}\n";
            }
        }

        return $output;
    }

    private function updateAppCssImport(string $themeId): void
    {
        $appCss = File::get($this->appCssPath);
        $importStatement = sprintf('@import "./themes/%s.css";', $themeId);

        if (str_contains($appCss, $importStatement)) {
            note('  - Import already exists in app.css');

            return;
        }

        $pattern = '/@import\s+["\']\.\/themes\/[^"\']+["\'];/';
        if (preg_match_all($pattern, $appCss, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $insertPosition = $lastMatch[1] + mb_strlen($lastMatch[0]);
            $appCss = mb_substr($appCss, 0, $insertPosition)."\n".$importStatement.mb_substr($appCss, $insertPosition);
        } else {
            $animatePattern = '/@import\s+["\']tw-animate-css["\'];/';
            if (preg_match($animatePattern, $appCss, $match, PREG_OFFSET_CAPTURE)) {
                $insertPosition = $match[0][1] + mb_strlen($match[0][0]);
                $appCss = mb_substr($appCss, 0, $insertPosition)."\n\n".$importStatement.mb_substr($appCss, $insertPosition);
            }
        }

        File::put($this->appCssPath, $appCss);
        info('  ✓ Updated app.css with theme import');
    }

    /**
     * @param  array<string, mixed>  $themeData
     */
    private function updateThemesConfig(array $themeData, string $themeId, string $themeName, string $description): void
    {
        $themesConfig = File::get($this->themesConfigPath);
        $cssVars = $themeData['cssVars'] ?? [];

        $font = 'System Sans';
        foreach (['theme', 'light'] as $section) {
            if (isset($cssVars[$section]['font-sans'])) {
                $fontValue = $cssVars[$section]['font-sans'];
                $font = mb_trim(explode(',', $fontValue)[0], "\"'");
                break;
            }
        }

        $lightVars = $cssVars['light'] ?? [];
        $primary = $lightVars['primary'] ?? 'oklch(0.5 0.1 200)';
        $secondary = $lightVars['secondary'] ?? 'oklch(0.8 0.05 200)';
        $accent = $lightVars['accent'] ?? 'oklch(0.7 0.1 200)';

        $colorThemePattern = '/export type ColorTheme = ([^\n]+)/';
        if (preg_match($colorThemePattern, $themesConfig, $matches)) {
            $existingTypes = $matches[1];
            if (! str_contains($existingTypes, sprintf('"%s"', $themeId))) {
                $newTypes = mb_rtrim($existingTypes).sprintf(' | "%s"', $themeId);
                $themesConfig = preg_replace($colorThemePattern, 'export type ColorTheme = '.$newTypes, $themesConfig);
            }
        }

        $themeConfigPattern = '/id:\s*["\']'.preg_quote($themeId, '/').'["\']/';
        if (preg_match($themeConfigPattern, (string) $themesConfig)) {
            info('  - Theme config already exists, updating...');
        } else {
            $newThemeConfig = <<<EOT
    {
        id: "{$themeId}",
        name: "{$themeName}",
        description: "{$description}",
        font: "{$font}",
        colors: {
            primary: "{$primary}",
            secondary: "{$secondary}",
            accent: "{$accent}",
        }
    }
EOT;

            // Remove the closing bracket and any trailing comma/whitespace before it
            $contentWithoutEnd = preg_replace('/,?\s*\n\];\s*$/', '', (string) $themesConfig);

            if ($contentWithoutEnd !== null && $contentWithoutEnd !== $themesConfig) {
                $themesConfig = $contentWithoutEnd.",\n{$newThemeConfig}\n];";
            } else {
                // Fallback to simpler append if regex fails
                $closingBracketPos = mb_strrpos((string) $themesConfig, ']');
                if ($closingBracketPos !== false) {
                    $beforeClosing = mb_substr((string) $themesConfig, 0, $closingBracketPos);
                    $afterClosing = mb_substr((string) $themesConfig, $closingBracketPos);
                    $beforeClosing = mb_rtrim($beforeClosing, ", \t\n\r\0\x0B"); // Trim comma and whitespace
                    $themesConfig = $beforeClosing.",\n".$newThemeConfig."\n".$afterClosing;
                }
            }
        }

        File::put($this->themesConfigPath, $themesConfig);
        info('  ✓ Updated themes.ts configuration');
    }
}
