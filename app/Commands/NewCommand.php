<?php

namespace App\Commands;

use App\Package\PackageScaffolder;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\select;

class NewCommand extends Command
{
    private const SUPPORTED_VERSIONS = [9, 10, 11, 12, 13];

    protected $signature = 'new
                            {package : The package name in vendor/name form}
                            {--path= : Target directory (defaults to ./name)}
                            {--laravel= : Laravel major version (9|10|11|12|13)}';

    protected $description = 'Scaffold a new Laravel package.';

    public function handle(PackageScaffolder $scaffolder): int
    {
        $package = (string) $this->argument('package');

        if (! preg_match('#^[a-z0-9][a-z0-9._-]*/[a-z0-9][a-z0-9._-]*$#', $package)) {
            $this->error("Invalid package name: {$package}. Expected vendor/name (lowercase).");
            return self::FAILURE;
        }

        $major = $this->resolveLaravelMajor();
        if ($major === null) {
            return self::FAILURE;
        }

        [$vendor, $name] = explode('/', $package, 2);
        $target = $this->option('path') ?: getcwd() . DIRECTORY_SEPARATOR . $name;

        $this->info("Scaffolding {$package} (Laravel {$major}) into {$target}");

        try {
            $result = $scaffolder->scaffold($vendor, $name, $target, $major);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Package ready: {$result['root']}");
        $this->line("Namespace: {$result['namespace']}");
        $this->line("Provider:  {$result['namespace']}\\{$result['providerClass']}");
        $this->line("Laravel:   {$result['laravelMajor']}");
        $this->newLine();
        $this->line("Next steps:");
        $this->line("  cd {$name}");
        $this->line("  zonda make:command Hello");
        $this->line("  zonda artisan list");

        return self::SUCCESS;
    }

    private function resolveLaravelMajor(): ?int
    {
        $raw = $this->option('laravel');

        if ($raw === null) {
            $picked = select(
                label: 'Laravel version',
                options: array_combine(
                    array_map('strval', self::SUPPORTED_VERSIONS),
                    array_map(fn ($v) => "Laravel {$v}", self::SUPPORTED_VERSIONS),
                ),
                default: '12',
            );
            return (int) $picked;
        }

        if (! ctype_digit((string) $raw)) {
            $this->error("Invalid --laravel value: {$raw}. Expected one of: " . implode(', ', self::SUPPORTED_VERSIONS));
            return null;
        }
        $major = (int) $raw;
        if (! in_array($major, self::SUPPORTED_VERSIONS, true)) {
            $this->error("Unsupported Laravel version: {$major}. Supported: " . implode(', ', self::SUPPORTED_VERSIONS));
            return null;
        }
        return $major;
    }
}
