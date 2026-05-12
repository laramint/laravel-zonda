<?php

namespace App\Support;

use App\Package\PackageContext;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;

abstract class AbstractMakeCommand extends Command
{
    abstract protected function stubName(): string;

    abstract protected function relativeTargetPath(PackageContext $pkg, string $name): string;

    /**
     * @return array<string, string>
     */
    abstract protected function replacements(PackageContext $pkg, string $name): array;

    public function handle(): int
    {
        try {
            $pkg = PackageContext::resolve();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $name = (string) $this->argument('name');
        $target = $pkg->root . DIRECTORY_SEPARATOR . $this->relativeTargetPath($pkg, $name);

        if (is_file($target) && ! $this->option('force')) {
            $this->error("File already exists: {$target} (use --force to overwrite)");
            return self::FAILURE;
        }

        $stubPath = base_path('app/Stubs/make/' . $this->stubName());
        if (! is_file($stubPath)) {
            $this->error("Stub not found: {$stubPath}");
            return self::FAILURE;
        }

        $content = strtr((string) file_get_contents($stubPath), $this->replacements($pkg, $name));

        $dir = dirname($target);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error("Failed to create directory: {$dir}");
            return self::FAILURE;
        }

        file_put_contents($target, $content);
        $this->info("Created: " . str_replace($pkg->root . DIRECTORY_SEPARATOR, '', $target));

        return self::SUCCESS;
    }

    /**
     * Split a make-command name into [subNamespaceSegments, className].
     * Accepts forward and back slashes (e.g. "Blog/Post" or "Blog\Post").
     *
     * @return array{0: list<string>, 1: string}
     */
    protected function parseName(string $raw): array
    {
        $normalized = str_replace('\\', '/', $raw);
        $segments = array_values(array_filter(
            array_map([$this, 'studly'], explode('/', $normalized)),
            fn ($s) => $s !== ''
        ));
        if ($segments === []) {
            return [[], ''];
        }
        $class = array_pop($segments);
        return [$segments, $class];
    }

    /**
     * Build a target namespace by joining the package namespace, an optional
     * type suffix (e.g. "Http\\Controllers"), and any subfolder segments.
     *
     * @param list<string> $subNs
     */
    protected function targetNamespace(PackageContext $pkg, string $typeSuffix, array $subNs): string
    {
        $parts = [$pkg->namespace];
        if ($typeSuffix !== '') {
            $parts[] = $typeSuffix;
        }
        foreach ($subNs as $seg) {
            $parts[] = $seg;
        }
        return implode('\\', $parts);
    }

    /**
     * Join a base relative directory, optional subfolder segments, and a filename.
     *
     * @param list<string> $subNs
     */
    protected function targetPath(string $baseDir, array $subNs, string $file): string
    {
        $parts = [trim($baseDir, '/')];
        foreach ($subNs as $seg) {
            $parts[] = $seg;
        }
        $parts[] = $file;
        return implode('/', $parts);
    }

    protected function studly(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? '';
        return str_replace(' ', '', ucwords($value));
    }
}
