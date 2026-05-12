<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class ViewMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:view {name : View name (e.g. admin.users.index or admin/users/index)} {--force}';

    protected $description = 'Generate a Blade view in the current package (resources/views).';

    protected function stubName(): string
    {
        return 'view.blade.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        $parts = $this->splitViewName($name);
        return 'resources/views/' . implode('/', $parts) . '.blade.php';
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        $parts = $this->splitViewName($name);
        return [
            '{{ViewName}}' => implode('.', $parts),
            '{{Title}}' => ucwords(str_replace(['-', '_', '.'], ' ', end($parts) ?: '')),
        ];
    }

    /**
     * @return list<string>
     */
    private function splitViewName(string $name): array
    {
        $normalized = str_replace(['\\', '/'], '.', $name);
        $segments = array_values(array_filter(
            array_map(fn ($s) => $this->kebab($s), explode('.', $normalized)),
            fn ($s) => $s !== ''
        ));
        if ($segments === []) {
            $segments = ['index'];
        }
        return $segments;
    }

    private function kebab(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $value) ?? $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? $value;
        return strtolower(trim($value, '-'));
    }
}
