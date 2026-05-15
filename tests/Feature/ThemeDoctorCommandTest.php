<?php

it('passes when required theme files exist', function () {
    $this->writeResourceFile('css/app.css', '');
    $this->writeResourceFile('js/conf/themes.ts', '');

    $this->artisan('theme:doctor')
        ->assertSuccessful();
});

it('fails when required theme files are missing', function () {
    $this->artisan('theme:doctor')
        ->assertFailed();
});
