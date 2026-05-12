<?php

namespace App\Package;

use RuntimeException;

class PackageContext
{
    public function __construct(
        public readonly string $root,
        public readonly string $vendor,
        public readonly string $name,
        public readonly string $namespace,
        public readonly string $providerClass,
        public readonly int $laravelMajor,
    ) {}

    public static function resolve(?string $startDir = null): self
    {
        $dir = $startDir ?? getcwd();

        while (true) {
            $composer = $dir . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($composer)) {
                $data = json_decode((string) file_get_contents($composer), true);
                if (is_array($data) && ($data['extra']['zonda']['package'] ?? false) === true) {
                    return self::fromComposer($dir, $data);
                }
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                throw new RuntimeException(
                    'Not inside a Zonda package. Run this command from a package created with `zonda new`.'
                );
            }
            $dir = $parent;
        }
    }

    public static function fromComposer(string $root, array $composer): self
    {
        $fullName = $composer['name'] ?? '';
        if (! str_contains($fullName, '/')) {
            throw new RuntimeException("Invalid package name in composer.json: {$fullName}");
        }
        [$vendor, $name] = explode('/', $fullName, 2);

        $autoload = $composer['autoload']['psr-4'] ?? [];
        $namespace = null;
        foreach ($autoload as $ns => $path) {
            if (trim($path, '/\\') === 'src') {
                $namespace = rtrim($ns, '\\');
                break;
            }
        }
        if ($namespace === null) {
            throw new RuntimeException('Could not determine package namespace (no PSR-4 entry mapped to src/).');
        }

        $providers = $composer['extra']['laravel']['providers'] ?? [];
        $providerFqn = $providers[0] ?? null;
        if (! $providerFqn) {
            throw new RuntimeException('No service provider declared in composer.json extra.laravel.providers.');
        }
        $providerClass = substr($providerFqn, strrpos($providerFqn, '\\') + 1);

        $laravelMajor = self::parseLaravelMajor($composer['extra']['zonda']['laravel'] ?? null);

        return new self($root, $vendor, $name, $namespace, $providerClass, $laravelMajor);
    }

    private static function parseLaravelMajor(mixed $raw): int
    {
        if ($raw === null || $raw === '') {
            throw new RuntimeException(
                'This package has no pinned Laravel version. Add "extra.zonda.laravel": "12" to composer.json.'
            );
        }
        if (is_int($raw)) {
            return self::assertSupported($raw);
        }
        $str = (string) $raw;
        if (preg_match('/(\d+)/', $str, $m)) {
            return self::assertSupported((int) $m[1]);
        }
        throw new RuntimeException("Cannot parse Laravel version from extra.zonda.laravel: {$str}");
    }

    private static function assertSupported(int $major): int
    {
        if ($major < 9 || $major > 13) {
            throw new RuntimeException("Unsupported Laravel version: {$major}. Supported: 9, 10, 11, 12, 13.");
        }
        return $major;
    }
}
