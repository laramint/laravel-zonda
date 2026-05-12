<?php

namespace App\Commands;

use App\Package\PackageScaffolder;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\multiselect;

class NewCommand extends Command
{
    private const SUPPORTED_VERSIONS = [9, 10, 11, 12, 13];

    protected $signature = 'new
                            {package : The package name in vendor/name form}
                            {--path= : Target directory (defaults to ./name)}
                            {--laravel= : Laravel major versions to support (comma-separated, e.g. 10,11,12)}';

    protected $description = 'Scaffold a new Laravel package.';

    public function handle(PackageScaffolder $scaffolder): int
    {
        $package = (string) $this->argument('package');

        if (! preg_match('#^[a-z0-9][a-z0-9._-]*/[a-z0-9][a-z0-9._-]*$#', $package)) {
            $this->error("Invalid package name: {$package}. Expected vendor/name (lowercase).");
            return self::FAILURE;
        }

        $majors = $this->resolveLaravelMajors();
        if ($majors === null) {
            return self::FAILURE;
        }

        [$vendor, $name] = explode('/', $package, 2);
        $target = $this->option('path') ?: getcwd() . DIRECTORY_SEPARATOR . $name;

        $this->info("Scaffolding {$package} (Laravel " . implode(', ', $majors) . ") into {$target}");

        try {
            $result = $scaffolder->scaffold($vendor, $name, $target, $majors);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Package ready: {$result['root']}");
        $this->line("Namespace: {$result['namespace']}");
        $this->line("Provider:  {$result['namespace']}\\{$result['providerClass']}");
        $this->line("Laravel:   " . implode(', ', $result['laravelMajors']));
        $this->newLine();
        $this->line("Next steps:");
        $this->line("  cd {$name}");
        $this->line("  zonda make:command Hello");
        $this->line("  zonda artisan list" . (count($result['laravelMajors']) > 1 ? '   # uses Laravel ' . max($result['laravelMajors']) . ' by default; pass --laravel=N to switch' : ''));

        return self::SUCCESS;
    }

    /**
     * @return list<int>|null  Null on validation failure (error already printed).
     */
    private function resolveLaravelMajors(): ?array
    {
        $raw = $this->option('laravel');

        if ($raw === null) {
            $picked = multiselect(
                label: 'Which Laravel versions should this package support?',
                options: array_combine(
                    array_map('strval', self::SUPPORTED_VERSIONS),
                    array_map(fn ($v) => "Laravel {$v}", self::SUPPORTED_VERSIONS),
                ),
                default: ['12'],
                required: true,
                hint: 'Use space to toggle, enter to confirm.',
            );
            return $this->normalize(array_map('intval', $picked));
        }

        $pieces = array_values(array_filter(array_map('trim', explode(',', (string) $raw)), fn ($s) => $s !== ''));
        if ($pieces === []) {
            $this->error('--laravel needs at least one version.');
            return null;
        }

        $majors = [];
        foreach ($pieces as $piece) {
            if (! ctype_digit($piece)) {
                $this->error("Invalid --laravel value: {$piece}. Expected one of: " . implode(', ', self::SUPPORTED_VERSIONS));
                return null;
            }
            $m = (int) $piece;
            if (! in_array($m, self::SUPPORTED_VERSIONS, true)) {
                $this->error("Unsupported Laravel version: {$m}. Supported: " . implode(', ', self::SUPPORTED_VERSIONS));
                return null;
            }
            $majors[] = $m;
        }
        return $this->normalize($majors);
    }

    /**
     * @param list<int> $majors
     * @return list<int>
     */
    private function normalize(array $majors): array
    {
        $majors = array_values(array_unique($majors));
        sort($majors);
        return $majors;
    }
}
