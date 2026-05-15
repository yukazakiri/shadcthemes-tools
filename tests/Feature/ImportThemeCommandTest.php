<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

it('imports a tweakcn theme into the generated theme config', function () {
    $this->writeResourceFile('css/app.css', <<<'CSS'
@import 'tailwindcss';
@import 'tw-animate-css';

@source '../views';
CSS);

    $this->writeResourceFile('js/conf/themes.ts', File::get($this->packagePath('stubs/vue/resources/js/conf/themes.ts')));

    Http::fake([
        'https://tweakcn.com/r/themes/vintage-paper.json' => Http::response([
            'name' => 'Vintage Paper',
            'cssVars' => [
                'theme' => [
                    'font-sans' => 'Merriweather, serif',
                ],
                'light' => [
                    'primary' => 'oklch(0.44 0.08 80)',
                    'secondary' => 'oklch(0.92 0.03 85)',
                    'accent' => 'oklch(0.86 0.05 75)',
                ],
                'dark' => [
                    'primary' => 'oklch(0.72 0.08 80)',
                ],
            ],
        ]),
    ]);

    $this->artisan('theme:import', ['url' => 'https://tweakcn.com/r/themes/vintage-paper.json'])
        ->assertSuccessful();

    expect(File::exists(resource_path('css/themes/vintage-paper.css')))->toBeTrue();

    $this->assertResourceFileContains('css/app.css', '@import "./themes/vintage-paper.css";');
    $this->assertResourceFileContains('js/conf/themes.ts', "| 'vintage-paper'");
    $this->assertResourceFileContains('js/conf/themes.ts', "id: 'vintage-paper'");
    $this->assertResourceFileContains('js/conf/themes.ts', "font: 'Merriweather'");
});

it('shows a setup hint before importing when theme files are missing', function () {
    $this->artisan('theme:import', ['url' => 'https://tweakcn.com/r/themes/vintage-paper.json'])
        ->assertFailed();
});
