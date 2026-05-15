<?php

use Illuminate\Support\Facades\File;

it('removes an installed theme with force', function () {
    $this->writeResourceFile('css/themes/forest.css', <<<'CSS'
.theme-forest {
  --primary: oklch(0.4 0.1 140);
}
CSS);

    $this->writeResourceFile('css/app.css', <<<'CSS'
@import './themes/rose.css';
@import './themes/forest.css';

@source '../views';
CSS);

    $this->writeResourceFile('js/conf/themes.ts', <<<'TS'
export type ColorTheme =
    | 'default'
    | 'forest';

export const colorThemes = [
    {
        id: 'default',
        name: 'Default',
    },
    {
        id: 'forest',
        name: 'Forest',
    },
];
TS);

    $this->artisan('theme:remove', ['theme' => 'forest', '--force' => true])
        ->assertSuccessful();

    expect(File::exists(resource_path('css/themes/forest.css')))->toBeFalse();
    $this->assertStringNotContainsString("@import './themes/forest.css';", File::get(resource_path('css/app.css')));
    $this->assertStringNotContainsString("| 'forest'", File::get(resource_path('js/conf/themes.ts')));
    $this->assertStringNotContainsString("id: 'forest'", File::get(resource_path('js/conf/themes.ts')));
});

it('does not remove the protected default theme', function () {
    $this->writeResourceFile('css/app.css', '');
    $this->writeResourceFile('js/conf/themes.ts', '');

    $this->artisan('theme:remove', ['theme' => 'default', '--force' => true])
        ->assertFailed();
});
