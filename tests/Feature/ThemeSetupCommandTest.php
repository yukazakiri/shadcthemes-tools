<?php

use Illuminate\Support\Facades\File;

it('sets up the react starter kit without overwriting prompts when forced', function () {
    $this->writeResourceFile('css/app.css', <<<'CSS'
@import 'tailwindcss';

@source '../views';
CSS);

    $this->writeResourceFile('js/app.tsx', <<<'TSX'
import { initializeTheme } from './hooks/use-appearance';

initializeTheme();
TSX);

    $this->artisan('theme:setup', ['--mode' => 'starter', '--stack' => 'react', '--force' => true])
        ->assertSuccessful();

    expect(File::exists(resource_path('js/conf/themes.ts')))->toBeTrue()
        ->and(File::exists(resource_path('js/components/theme-switcher.tsx')))->toBeTrue()
        ->and(File::exists(resource_path('js/hooks/use-color-theme.tsx')))->toBeTrue();

    $this->assertResourceFileContains('css/app.css', "@import './themes/rose.css';");
    $this->assertResourceFileContains('css/app.css', "@import './themes/ocean.css';");
    $this->assertResourceFileContains('js/app.tsx', "import { initializeColorTheme } from './hooks/use-color-theme';");
    $this->assertResourceFileContains('js/app.tsx', 'initializeColorTheme();');
});

it('can skip existing files during setup', function () {
    $this->writeResourceFile('css/app.css', '');
    $this->writeResourceFile('js/conf/themes.ts', '// existing config');

    $this->artisan('theme:setup', ['--mode' => 'starter', '--stack' => 'react', '--skip-existing' => true])
        ->assertSuccessful();

    expect(File::get(resource_path('js/conf/themes.ts')))->toBe('// existing config');
});

it('rejects invalid non-interactive setup choices', function () {
    $this->artisan('theme:setup', ['--mode' => 'invalid', '--stack' => 'react'])
        ->assertFailed();
});
