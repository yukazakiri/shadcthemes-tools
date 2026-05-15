<?php

use Illuminate\Support\Facades\File;

it('updates react theme components with an explicit stack', function () {
    $this->artisan('theme:update', ['--stack' => 'react', '--force' => true])
        ->assertSuccessful();

    expect(File::exists(resource_path('js/components/theme-switcher.tsx')))->toBeTrue();
});

it('detects vue stack from package json', function () {
    File::put(base_path('package.json'), json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^2.0',
        ],
    ]));

    $this->artisan('theme:update', ['--force' => true])
        ->assertSuccessful();

    expect(File::exists(resource_path('js/components/ThemeSwitcher.vue')))->toBeTrue();
});

it('rejects invalid update stack values', function () {
    $this->artisan('theme:update', ['--stack' => 'svelte', '--force' => true])
        ->assertFailed();
});
