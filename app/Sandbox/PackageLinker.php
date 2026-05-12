<?php

namespace App\Sandbox;

use App\Package\PackageContext;
use RuntimeException;
use Symfony\Component\Process\Process;

class PackageLinker
{
    public function __construct(private readonly SandboxManager $sandbox) {}

    public function link(PackageContext $pkg): void
    {
        if ($this->currentlyLinked() === $pkg->root) {
            return;
        }

        $sandboxComposer = $this->sandbox->path() . '/composer.json';
        if (! is_file($sandboxComposer)) {
            throw new RuntimeException("Sandbox composer.json missing: {$sandboxComposer}");
        }

        $data = json_decode((string) file_get_contents($sandboxComposer), true) ?: [];

        $data['repositories'] = $this->upsertPathRepository(
            $data['repositories'] ?? [],
            $pkg->root
        );

        $packageName = "{$pkg->vendor}/{$pkg->name}";
        $data['require'] ??= [];
        $data['require'][$packageName] = '*';
        $data['minimum-stability'] = 'dev';
        $data['prefer-stable'] = true;

        file_put_contents(
            $sandboxComposer,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $this->runComposer(['composer', 'update', $packageName, '--no-interaction', '--with-all-dependencies']);

        $this->writeState($pkg->root);
    }

    public function currentlyLinked(): ?string
    {
        $state = $this->readState();
        return $state['linked'] ?? null;
    }

    /**
     * Force a fresh link even when state.json already matches the package's
     * current root. Used by `zonda resync` to recover from cases where the
     * sandbox composer.json or vendor tree has drifted out of step with the
     * package on disk.
     */
    public function relink(PackageContext $pkg): void
    {
        $path = $this->sandbox->statePath();
        if (is_file($path)) {
            @unlink($path);
        }
        $this->link($pkg);
    }

    private function upsertPathRepository(array $repositories, string $absolutePath): array
    {
        $entry = [
            'type' => 'path',
            'url' => $absolutePath,
            'options' => ['symlink' => true],
        ];

        $out = [];
        $index = 0;
        foreach ($repositories as $key => $repo) {
            if (is_array($repo) && ($repo['type'] ?? null) === 'path' && isset($repo['url'])) {
                // Drop any existing path repo (we manage exactly one).
                continue;
            }
            $newKey = is_int($key) ? 'repo' . $index++ : $key;
            $out[$newKey] = $repo;
        }
        $out['zonda-package'] = $entry;
        return $out;
    }

    protected function runComposer(array $cmd): void
    {
        $process = new Process($cmd, $this->sandbox->path(), null, null, null);
        $process->setTty(Process::isTtySupported());
        $process->run(function ($type, $buffer) {
            fwrite($type === Process::ERR ? STDERR : STDOUT, $buffer);
        });
        if (! $process->isSuccessful()) {
            throw new RuntimeException('composer ' . implode(' ', array_slice($cmd, 1)) . ' failed in sandbox');
        }
    }

    private function readState(): array
    {
        $path = $this->sandbox->statePath();
        if (! is_file($path)) {
            return [];
        }
        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    private function writeState(string $linkedPath): void
    {
        $path = $this->sandbox->statePath();
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, json_encode(['linked' => $linkedPath], JSON_PRETTY_PRINT) . "\n");
    }
}
