<?php

use App\Package\PackageScaffolder;

beforeEach(function () {
    $this->tmp = sys_get_temp_dir() . '/zonda-test-' . uniqid();
    mkdir($this->tmp, 0755, true);
});

afterEach(function () {
    rrmdir($this->tmp);
});

it('scaffolds a new package skeleton with the default Laravel version', function () {
    $scaffolder = new PackageScaffolder();
    $result = $scaffolder->scaffold('acme', 'demo', $this->tmp . '/demo');

    expect($result['namespace'])->toBe('Acme\\Demo')
        ->and($result['providerClass'])->toBe('DemoServiceProvider')
        ->and($result['laravelMajors'])->toBe([12]);

    expect(is_file($this->tmp . '/demo/composer.json'))->toBeTrue()
        ->and(is_file($this->tmp . '/demo/src/DemoServiceProvider.php'))->toBeTrue()
        ->and(is_file($this->tmp . '/demo/tests/Pest.php'))->toBeTrue()
        ->and(is_file($this->tmp . '/demo/tests/TestCase.php'))->toBeTrue()
        ->and(is_file($this->tmp . '/demo/phpunit.xml.dist'))->toBeTrue()
        ->and(is_file($this->tmp . '/demo/README.md'))->toBeTrue()
        ->and(is_file($this->tmp . '/demo/.gitignore'))->toBeTrue();

    $composer = json_decode(file_get_contents($this->tmp . '/demo/composer.json'), true);
    expect($composer['name'])->toBe('acme/demo')
        ->and($composer['autoload']['psr-4'])->toHaveKey('Acme\\Demo\\')
        ->and($composer['autoload']['psr-4']['Acme\\Demo\\'])->toBe('src/')
        ->and($composer['extra']['zonda']['package'])->toBeTrue()
        ->and($composer['extra']['zonda']['laravel'])->toBe([12])
        ->and($composer['require']['illuminate/support'])->toBe('^12.0')
        ->and($composer['require-dev']['orchestra/testbench'])->toBe('^10.0')
        ->and($composer['extra']['laravel']['providers'])->toBe(['Acme\\Demo\\DemoServiceProvider']);

    $provider = file_get_contents($this->tmp . '/demo/src/DemoServiceProvider.php');
    expect($provider)->toContain('namespace Acme\\Demo;')
        ->and($provider)->toContain('class DemoServiceProvider')
        ->and($provider)->toContain("loadViewsFrom(\$base . '/resources/views', 'demo')")
        ->and($provider)->toContain("mergeConfigFrom(\$config, 'demo')")
        ->and($provider)->toContain("loadMigrationsFrom(\$base . '/database/migrations')");
});

it('pins the package to a single chosen Laravel major', function () {
    $scaffolder = new PackageScaffolder();
    $result = $scaffolder->scaffold('acme', 'demo', $this->tmp . '/demo', [10]);

    $composer = json_decode(file_get_contents($this->tmp . '/demo/composer.json'), true);
    expect($result['laravelMajors'])->toBe([10])
        ->and($composer['extra']['zonda']['laravel'])->toBe([10])
        ->and($composer['require']['illuminate/support'])->toBe('^10.0')
        ->and($composer['require-dev']['orchestra/testbench'])->toBe('^8.0');
});

it('writes a union constraint when multiple Laravel majors are selected', function () {
    $scaffolder = new PackageScaffolder();
    $result = $scaffolder->scaffold('acme', 'demo', $this->tmp . '/demo', [10, 11, 12]);

    $composer = json_decode(file_get_contents($this->tmp . '/demo/composer.json'), true);
    expect($result['laravelMajors'])->toBe([10, 11, 12])
        ->and($composer['extra']['zonda']['laravel'])->toBe([10, 11, 12])
        ->and($composer['require']['illuminate/support'])->toBe('^10.0|^11.0|^12.0')
        ->and($composer['require-dev']['orchestra/testbench'])->toBe('^8.0|^9.0|^10.0');
});

it('accepts a legacy single-int call', function () {
    $scaffolder = new PackageScaffolder();
    $result = $scaffolder->scaffold('acme', 'demo', $this->tmp . '/demo', 11);

    expect($result['laravelMajors'])->toBe([11]);
});

it('rejects unsupported Laravel versions', function () {
    expect(fn () => (new PackageScaffolder())->scaffold('acme', 'demo', $this->tmp . '/demo', [8]))
        ->toThrow(RuntimeException::class, 'Unsupported Laravel version');
});

it('refuses to scaffold into a non-empty directory', function () {
    mkdir($this->tmp . '/demo');
    file_put_contents($this->tmp . '/demo/x', 'x');

    $scaffolder = new PackageScaffolder();
    expect(fn() => $scaffolder->scaffold('acme', 'demo', $this->tmp . '/demo'))
        ->toThrow(RuntimeException::class);
});

it('writes the chosen Laravel version when invoked via the new command', function () {
    $target = $this->tmp . '/widget';

    $this->artisan('new', ['package' => 'acme/widget', '--path' => $target, '--laravel' => '11'])
        ->assertExitCode(0);

    $composer = json_decode(file_get_contents($target . '/composer.json'), true);
    expect($composer['extra']['zonda']['laravel'])->toBe([11])
        ->and($composer['require']['illuminate/support'])->toBe('^11.0')
        ->and($composer['require-dev']['orchestra/testbench'])->toBe('^9.0');
});

it('accepts a comma-separated --laravel option', function () {
    $target = $this->tmp . '/widget';

    $this->artisan('new', ['package' => 'acme/widget', '--path' => $target, '--laravel' => '10,11,12'])
        ->assertExitCode(0);

    $composer = json_decode(file_get_contents($target . '/composer.json'), true);
    expect($composer['extra']['zonda']['laravel'])->toBe([10, 11, 12])
        ->and($composer['require']['illuminate/support'])->toBe('^10.0|^11.0|^12.0');
});

it('rejects an invalid --laravel option', function () {
    $this->artisan('new', ['package' => 'acme/widget', '--path' => $this->tmp . '/widget', '--laravel' => '7'])
        ->assertExitCode(1);
});

it('rejects an empty --laravel option', function () {
    $this->artisan('new', ['package' => 'acme/widget', '--path' => $this->tmp . '/widget', '--laravel' => ','])
        ->assertExitCode(1);
});
