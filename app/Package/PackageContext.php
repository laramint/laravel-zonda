<?php

namespace App\Package;

use RuntimeException;

class PackageContext
{
    public const SUPPORTED_MAJORS = [9, 10, 11, 12, 13];

    /**
     * @param list<int> $laravelMajors  Sorted ascending, deduplicated, all in SUPPORTED_MAJORS.
     */
    public function __construct(
        public readonly string $root,
        public readonly string $vendor,
        public readonly string $name,
        public readonly string $namespace,
        public readonly string $providerClass,
        public readonly array $laravelMajors,
    ) {}

    /**
     * The version to use when the user hasn't pinned a specific one
     * (e.g. `zonda artisan` without `--laravel`): highest supported major.
     */
    public function defaultLaravelMajor(): int
    {
        return max($this->laravelMajors);
    }

    public function supportsLaravel(int $major): bool
    {
        return in_array($major, $this->laravelMajors, true);
    }

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

        $laravelMajors = self::parseLaravelMajors($composer['extra']['zonda']['laravel'] ?? null);

        return new self($root, $vendor, $name, $namespace, $providerClass, $laravelMajors);
    }

    /**
     * Accepts:
     *   - int (e.g. 12)
     *   - string constraint (e.g. "^12.0")
     *   - comma-separated string (e.g. "10,11,12")
     *   - array of any of the above
     *
     * @return list<int>
     */
    private static function parseLaravelMajors(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            throw new RuntimeException(
                'This package has no pinned Laravel version. Add "extra.zonda.laravel": [12] (or a single int) to composer.json.'
            );
        }

        $items = is_array($raw) ? $raw : [$raw];
        $majors = [];
        foreach ($items as $item) {
            if (is_string($item) && str_contains($item, ',')) {
                foreach (explode(',', $item) as $piece) {
                    $majors[] = self::extractMajor(trim($piece));
                }
            } else {
                $majors[] = self::extractMajor($item);
            }
        }

        $majors = array_values(array_unique($majors));
        sort($majors);

        foreach ($majors as $m) {
            if (! in_array($m, self::SUPPORTED_MAJORS, true)) {
                throw new RuntimeException("Unsupported Laravel version: {$m}. Supported: " . implode(', ', self::SUPPORTED_MAJORS) . '.');
            }
        }
        return $majors;
    }

    private static function extractMajor(mixed $raw): int
    {
        if (is_int($raw)) {
            return $raw;
        }
        $str = (string) $raw;
        if (preg_match('/(\d+)/', $str, $m)) {
            return (int) $m[1];
        }
        throw new RuntimeException("Cannot parse Laravel version from extra.zonda.laravel: {$str}");
    }
}
