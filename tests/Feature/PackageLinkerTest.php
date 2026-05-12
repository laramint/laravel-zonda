<?php

use App\Package\PackageContext;
use App\Sandbox\SandboxManager;

beforeEach(function () {
    $this->tmp = sys_get_temp_dir() . '/zonda-linker-' . uniqid();
    mkdir($this->tmp . '/.zonda/sandboxes/laravel-12', 0755, true);
    // Minimal fake sandbox composer.json
    file_put_contents($this->tmp . '/.zonda/sandboxes/laravel-12/composer.json', json_encode([
        'name' => 'fake/sandbox',
        'require' => ['laravel/framework' => '^12.0'],
    ], JSON_PRETTY_PRINT));
});

afterEach(function () {
    rrmdir($this->tmp);
});

function makePkgComposer(string $packageRoot, int $major = 12): void
{
    mkdir($packageRoot, 0755, true);
    file_put_contents($packageRoot . '/composer.json', json_encode([
        'name' => 'acme/widget',
        'autoload' => ['psr-4' => ['Acme\\Widget\\' => 'src/']],
        'extra' => [
            'zonda' => ['package' => true, 'laravel' => $major],
            'laravel' => ['providers' => ['Acme\\Widget\\WidgetServiceProvider']],
        ],
    ]));
}

it('writes a path repository and require entry into sandbox composer.json', function () {
    $packageRoot = $this->tmp . '/pkg';
    makePkgComposer($packageRoot);

    $sandbox = new SandboxManager(12, $this->tmp);
    $linker = new TestablePackageLinker($sandbox);
    $pkg = PackageContext::resolve($packageRoot);
    $linker->link($pkg);

    $composer = json_decode(file_get_contents($this->tmp . '/.zonda/sandboxes/laravel-12/composer.json'), true);
    expect($composer['repositories']['zonda-package']['type'])->toBe('path')
        ->and($composer['repositories']['zonda-package']['url'])->toBe($packageRoot)
        ->and($composer['repositories']['zonda-package']['options']['symlink'])->toBeTrue()
        ->and($composer['require']['acme/widget'])->toBe('*');

    $state = json_decode(file_get_contents($this->tmp . '/.zonda/sandboxes/laravel-12/state.json'), true);
    expect($state['linked'])->toBe($packageRoot);

    expect($linker->composerCalls)->toHaveCount(1);
});

it('skips relinking when the same package is already linked', function () {
    $packageRoot = $this->tmp . '/pkg';
    makePkgComposer($packageRoot);
    file_put_contents($this->tmp . '/.zonda/sandboxes/laravel-12/state.json', json_encode(['linked' => $packageRoot]));

    $sandbox = new SandboxManager(12, $this->tmp);
    $linker = new TestablePackageLinker($sandbox);
    $pkg = PackageContext::resolve($packageRoot);
    $linker->link($pkg);

    expect($linker->composerCalls)->toHaveCount(0);
});
