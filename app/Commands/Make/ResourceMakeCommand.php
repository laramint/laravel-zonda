<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class ResourceMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:resource {name : Resource class name} {--force}';

    protected $description = 'Generate an API resource in the current package.';

    protected function stubName(): string
    {
        return 'resource.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Http/Resources', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Http\\Resources', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
