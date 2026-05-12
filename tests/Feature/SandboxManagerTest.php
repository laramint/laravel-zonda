<?php

use App\Sandbox\SandboxManager;
use App\Sandbox\SandboxManagerFactory;

beforeEach(function () {
    $this->tmp = sys_get_temp_dir() . '/zonda-sandbox-' . uniqid();
    mkdir($this->tmp, 0755, true);
});

afterEach(function () {
    rrmdir($this->tmp);
});

it('reports a version-scoped sandbox path under the home directory', function () {
    $sm = new SandboxManager(12, $this->tmp);
    expect($sm->path())->toBe($this->tmp . '/.zonda/sandboxes/laravel-12')
        ->and($sm->statePath())->toBe($this->tmp . '/.zonda/sandboxes/laravel-12/state.json')
        ->and($sm->major)->toBe(12)
        ->and($sm->exists())->toBeFalse();
});

it('isolates sandboxes per Laravel major', function () {
    $l10 = new SandboxManager(10, $this->tmp);
    $l13 = new SandboxManager(13, $this->tmp);
    expect($l10->path())->not->toBe($l13->path())
        ->and($l10->path())->toContain('laravel-10')
        ->and($l13->path())->toContain('laravel-13');
});

it('rejects unsupported Laravel versions', function () {
    expect(fn () => new SandboxManager(8, $this->tmp))
        ->toThrow(RuntimeException::class, 'Unsupported Laravel version');
});

it('treats a directory with an artisan file as existing', function () {
    mkdir($this->tmp . '/.zonda/sandboxes/laravel-12', 0755, true);
    file_put_contents($this->tmp . '/.zonda/sandboxes/laravel-12/artisan', '<?php');

    $sm = new SandboxManager(12, $this->tmp);
    expect($sm->exists())->toBeTrue();

    // ensure() is idempotent when the sandbox already exists.
    $sm->ensure();
    expect($sm->exists())->toBeTrue();
});

it('reset removes the version-scoped sandbox directory', function () {
    mkdir($this->tmp . '/.zonda/sandboxes/laravel-12/nested', 0755, true);
    file_put_contents($this->tmp . '/.zonda/sandboxes/laravel-12/artisan', '<?php');
    file_put_contents($this->tmp . '/.zonda/sandboxes/laravel-12/state.json', '{}');

    $sm = new SandboxManager(12, $this->tmp);
    $sm->reset();

    expect(is_dir($this->tmp . '/.zonda/sandboxes/laravel-12'))->toBeFalse();
});

it('factory caches sandbox managers per version', function () {
    $factory = new SandboxManagerFactory($this->tmp);
    $a = $factory->for(12);
    $b = $factory->for(12);
    $c = $factory->for(11);

    expect($a)->toBe($b)
        ->and($a)->not->toBe($c)
        ->and($c->major)->toBe(11);
});
