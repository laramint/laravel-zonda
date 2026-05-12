<?php

use App\Package\PackageContext;
use App\Package\PackageScaffolder;

beforeEach(function () {
    $this->tmp = sys_get_temp_dir() . '/zonda-ctx-' . uniqid();
    mkdir($this->tmp, 0755, true);
});

afterEach(function () {
    rrmdir($this->tmp);
});

it('resolves a package from its root directory', function () {
    (new PackageScaffolder())->scaffold('acme', 'widget', $this->tmp . '/widget', 12);

    $ctx = PackageContext::resolve($this->tmp . '/widget');
    expect($ctx->vendor)->toBe('acme')
        ->and($ctx->name)->toBe('widget')
        ->and($ctx->namespace)->toBe('Acme\\Widget')
        ->and($ctx->providerClass)->toBe('WidgetServiceProvider')
        ->and($ctx->laravelMajor)->toBe(12);
});

it('resolves a package from a nested subdirectory', function () {
    (new PackageScaffolder())->scaffold('acme', 'widget', $this->tmp . '/widget', 11);
    mkdir($this->tmp . '/widget/src/Deep/Nest', 0755, true);

    $ctx = PackageContext::resolve($this->tmp . '/widget/src/Deep/Nest');
    expect($ctx->name)->toBe('widget')
        ->and($ctx->laravelMajor)->toBe(11);
});

it('parses a string Laravel constraint', function () {
    (new PackageScaffolder())->scaffold('acme', 'widget', $this->tmp . '/widget', 12);
    // Rewrite to a string constraint
    $composerPath = $this->tmp . '/widget/composer.json';
    $data = json_decode(file_get_contents($composerPath), true);
    $data['extra']['zonda']['laravel'] = '^10.0';
    file_put_contents($composerPath, json_encode($data, JSON_PRETTY_PRINT));

    $ctx = PackageContext::resolve($this->tmp . '/widget');
    expect($ctx->laravelMajor)->toBe(10);
});

it('throws when the Laravel version is missing from composer.json', function () {
    (new PackageScaffolder())->scaffold('acme', 'widget', $this->tmp . '/widget', 12);
    $composerPath = $this->tmp . '/widget/composer.json';
    $data = json_decode(file_get_contents($composerPath), true);
    unset($data['extra']['zonda']['laravel']);
    file_put_contents($composerPath, json_encode($data, JSON_PRETTY_PRINT));

    expect(fn () => PackageContext::resolve($this->tmp . '/widget'))
        ->toThrow(RuntimeException::class, 'no pinned Laravel version');
});

it('throws when not inside a Zonda package', function () {
    expect(fn() => PackageContext::resolve($this->tmp))
        ->toThrow(RuntimeException::class, 'Not inside a Zonda package');
});
