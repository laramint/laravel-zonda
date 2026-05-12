<?php

it('refuses to self-update when not running from a PHAR', function () {
    // The test suite runs from source, so Phar::running() returns ''.
    // The command must abort and point Composer users at the right install path.
    $this->artisan('self-update')
        ->expectsOutputToContain('only works when Zonda is installed as a PHAR')
        ->expectsOutputToContain('composer global update laramint/laravel-zonda -W')
        ->assertExitCode(1);
});

it('aliases the command as `update`', function () {
    $this->artisan('update')
        ->expectsOutputToContain('only works when Zonda is installed as a PHAR')
        ->assertExitCode(1);
});
