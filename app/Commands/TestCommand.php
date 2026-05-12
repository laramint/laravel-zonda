<?php

namespace App\Commands;

use App\Package\PackageContext;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;
use Symfony\Component\Process\Process;

class TestCommand extends Command
{
    protected $signature = 'test {args?* : Arguments to pass to pest/phpunit}';

    protected $description = 'Run the current package test suite.';

    public function handle(): int
    {
        try {
            $pkg = PackageContext::resolve();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (! is_dir($pkg->root . '/vendor')) {
            $this->info('Installing package dependencies...');
            $install = new Process(['composer', 'install', '--no-interaction'], $pkg->root, null, null, null);
            $install->setTty(Process::isTtySupported());
            $install->run(function ($type, $buffer) {
                fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
            });
            if (! $install->isSuccessful()) {
                $this->error('composer install failed in package.');
                return self::FAILURE;
            }
        }

        $runner = is_file($pkg->root . '/vendor/bin/pest')
            ? $pkg->root . '/vendor/bin/pest'
            : (is_file($pkg->root . '/vendor/bin/phpunit') ? $pkg->root . '/vendor/bin/phpunit' : null);

        if (! $runner) {
            $this->error('No test runner found (looked for vendor/bin/pest and vendor/bin/phpunit).');
            return self::FAILURE;
        }

        $args = (array) ($this->argument('args') ?: []);
        $process = new Process(array_merge([$runner], $args), $pkg->root, null, null, null);
        $process->setTty(Process::isTtySupported());
        $process->run(function ($type, $buffer) {
            fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
        });

        return $process->getExitCode() ?? self::FAILURE;
    }
}
