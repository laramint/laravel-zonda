<?php

namespace App\Commands;

use App\Package\PackageContext;
use App\Sandbox\PackageLinker;
use App\Sandbox\SandboxManager;
use App\Sandbox\SandboxManagerFactory;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;

class ResyncCommand extends Command
{
    protected $signature = 'resync
                            {--laravel= : Resync only this Laravel major (must be in the pinned set)}
                            {--all : Resync every pinned Laravel major}
                            {--reset : Delete the chosen sandbox(es) and rebuild from scratch}';

    protected $description = 'Re-link the current package into its Zonda sandbox(es). Useful after moving the package directory or when the sandbox is out of sync.';

    public function handle(SandboxManagerFactory $factory): int
    {
        try {
            $pkg = PackageContext::resolve();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $majors = $this->resolveMajors($pkg);
        if ($majors === null) {
            return self::FAILURE;
        }

        foreach ($majors as $major) {
            $sandbox = $factory->for($major);
            $this->resyncOne($pkg, $sandbox, $major);
        }

        $this->info('Resync complete.');
        return self::SUCCESS;
    }

    private function resyncOne(PackageContext $pkg, SandboxManager $sandbox, int $major): void
    {
        $linker = new PackageLinker($sandbox);
        $previous = $linker->currentlyLinked();

        $this->line("<info>[Laravel {$major}]</info>");
        $this->line("  sandbox:  {$sandbox->path()}");
        $this->line('  previous: ' . ($previous ?? '(none)'));
        $this->line("  current:  {$pkg->root}");

        if ($this->option('reset')) {
            $this->line('  resetting sandbox...');
            $sandbox->reset();
        }

        if (! $sandbox->exists()) {
            $this->line('  creating sandbox (first-time install for this version)...');
        }
        $sandbox->ensure();

        try {
            $linker->relink($pkg);
        } catch (RuntimeException $e) {
            $this->error("  failed: " . $e->getMessage());
            throw $e;
        }

        $this->line("  ✓ linked");
    }

    /**
     * @return list<int>|null
     */
    private function resolveMajors(PackageContext $pkg): ?array
    {
        $hasAll = (bool) $this->option('all');
        $only = $this->option('laravel');

        if ($hasAll && $only !== null) {
            $this->error('--all and --laravel are mutually exclusive.');
            return null;
        }

        if ($hasAll) {
            return $pkg->laravelMajors;
        }

        if ($only !== null) {
            if (! ctype_digit((string) $only)) {
                $this->error("Invalid --laravel value: {$only}.");
                return null;
            }
            $major = (int) $only;
            if (! $pkg->supportsLaravel($major)) {
                $this->error(
                    "Laravel {$major} is not in this package's pinned set ("
                    . implode(', ', $pkg->laravelMajors) . ')'
                );
                return null;
            }
            return [$major];
        }

        return [$pkg->defaultLaravelMajor()];
    }
}
