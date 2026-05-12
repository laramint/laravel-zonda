<?php

namespace App\Sandbox;

use RuntimeException;
use Symfony\Component\Process\Process;

class SandboxManager
{
    private string $home;

    public function __construct(
        public readonly int $major,
        ?string $homeDir = null,
    ) {
        if ($major < 9 || $major > 13) {
            throw new RuntimeException("Unsupported Laravel version: {$major}. Supported: 9, 10, 11, 12, 13.");
        }
        $this->home = $homeDir ?? ($_SERVER['HOME'] ?? getenv('HOME') ?: sys_get_temp_dir());
    }

    public function path(): string
    {
        return $this->home . '/.zonda/sandboxes/laravel-' . $this->major;
    }

    public function statePath(): string
    {
        return $this->path() . '/state.json';
    }

    public function exists(): bool
    {
        return is_dir($this->path()) && is_file($this->path() . '/artisan');
    }

    public function ensure(?callable $onCreate = null): void
    {
        if ($this->exists()) {
            return;
        }

        $sandbox = $this->path();
        $parent = dirname($sandbox);
        if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
            throw new RuntimeException("Failed to create {$parent}");
        }

        if ($onCreate) {
            $onCreate($sandbox);
        }

        $constraint = "laravel/laravel:^{$this->major}.0";
        $process = new Process(
            ['composer', 'create-project', $constraint, $sandbox, '--no-interaction', '--prefer-dist'],
            $parent,
            null,
            null,
            null
        );
        $process->setTty(Process::isTtySupported());
        $process->run(function ($type, $buffer) {
            fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
        });

        if (! $process->isSuccessful() || ! $this->exists()) {
            throw new RuntimeException('Failed to create Laravel sandbox at ' . $sandbox);
        }
    }

    public function reset(): void
    {
        $sandbox = $this->path();
        if (is_dir($sandbox)) {
            $this->rrmdir($sandbox);
        }
    }

    private function rrmdir(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_link($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
