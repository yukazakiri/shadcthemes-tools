<?php

use Illuminate\Contracts\Console\Kernel;

it('registers the theme artisan commands', function () {
    $commands = app(Kernel::class)->all();

    expect($commands)->toHaveKeys([
        'theme:add',
        'theme:doctor',
        'theme:import',
        'theme:remove',
        'theme:setup',
        'theme:update',
    ]);
});
