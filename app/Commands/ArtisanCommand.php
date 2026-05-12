<?php

namespace App\Commands;

use App\Package\PackageContext;
use App\Sandbox\PackageLinker;
use App\Sandbox\SandboxManagerFactory;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;
use Symfony\Component\Process\Process;

class ArtisanCommand extends Command
{
    protected $signature = 'artisan {args?* : Arguments to pass to artisan}';

    protected $description = 'Run an artisan command inside the Zonda sandbox with the current package linked.';

    public function handle(SandboxManagerFactory $factory): int
    {
        try {
            $pkg = PackageContext::resolve();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $sandbox = $factory->for($pkg->laravelMajor);
        $linker = new PackageLinker($sandbox);

        if (! $sandbox->exists()) {
            $this->info("Creating Laravel {$pkg->laravelMajor} sandbox at {$sandbox->path()} (first run, this may take a minute)...");
        }
        $sandbox->ensure();

        try {
            $linker->link($pkg);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $args = (array) ($this->argument('args') ?: []);
        $cmd = array_merge([PHP_BINARY, 'artisan'], $args);

        $process = new Process($cmd, $sandbox->path(), null, null, null);
        $process->setTty(Process::isTtySupported());
        $process->run(function ($type, $buffer) {
            fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
        });

        return $process->getExitCode() ?? self::FAILURE;
    }
}
