<?php

it('greets the world by default', function () {
    $this->artisan('hello')
        ->expectsOutputToContain('Hello, world, from Zonda!')
        ->assertExitCode(0);
});

it('greets a named target', function () {
    $this->artisan('hello Alice')
        ->expectsOutputToContain('Hello, Alice, from Zonda!')
        ->assertExitCode(0);
});

it('shouts when --shout is passed', function () {
    $this->artisan('hello Alice --shout')
        ->expectsOutputToContain('HELLO, ALICE, FROM ZONDA!')
        ->assertExitCode(0);
});
