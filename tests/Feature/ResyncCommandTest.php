<?php

use App\Package\PackageContext;
use App\Sandbox\SandboxManager;

beforeEach(function () {
    $this->tmp = sys_get_temp_dir() . '/zonda-resync-' . uniqid();
    mkdir($this->tmp . '/.zonda/sandboxes/laravel-12', 0755, true);
    file_put_contents($this->tmp . '/.zonda/sandboxes/laravel-12/composer.json', json_encode([
        'name' => 'fake/sandbox',
        'require' => ['laravel/framework' => '^12.0'],
    ], JSON_PRETTY_PRINT));
    // Fake artisan so SandboxManager::exists() returns true.
    file_put_contents($this->tmp . '/.zonda/sandboxes/laravel-12/artisan', '<?php');
});

afterEach(function () {
    rrmdir($this->tmp);
});

it('forces a re-link even when state already matches the current package', function () {
    $packageRoot = $this->tmp . '/pkg';
    makePkgComposer($packageRoot, 12);

    // Seed state.json with the same path as $pkg->root — link() would normally
    // early-return. relink() must NOT early-return.
    $statePath = $this->tmp . '/.zonda/sandboxes/laravel-12/state.json';
    file_put_contents($statePath, json_encode(['linked' => $packageRoot]));

    $sandbox = new SandboxManager(12, $this->tmp);
    $linker = new TestablePackageLinker($sandbox);
    $pkg = PackageContext::resolve($packageRoot);

    $linker->relink($pkg);

    expect($linker->composerCalls)->toHaveCount(1);

    $composer = json_decode(file_get_contents($this->tmp . '/.zonda/sandboxes/laravel-12/composer.json'), true);
    expect($composer['repositories']['zonda-package']['url'])->toBe($packageRoot);
});

it('rewires the path repository when the package directory has moved', function () {
    $oldRoot = $this->tmp . '/old';
    $newRoot = $this->tmp . '/new';

    // Sandbox state still points at the old path (left behind from a previous
    // session — the directory was renamed under the user's feet).
    file_put_contents(
        $this->tmp . '/.zonda/sandboxes/laravel-12/state.json',
        json_encode(['linked' => $oldRoot])
    );

    // The package now lives at $newRoot.
    makePkgComposer($newRoot, 12);

    $sandbox = new SandboxManager(12, $this->tmp);
    $linker = new TestablePackageLinker($sandbox);
    $pkg = PackageContext::resolve($newRoot);

    $linker->relink($pkg);

    $composer = json_decode(file_get_contents($this->tmp . '/.zonda/sandboxes/laravel-12/composer.json'), true);
    expect($composer['repositories']['zonda-package']['url'])->toBe($newRoot)
        ->and(json_decode(file_get_contents($this->tmp . '/.zonda/sandboxes/laravel-12/state.json'), true)['linked'])
            ->toBe($newRoot);
});
