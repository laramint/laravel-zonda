<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class MiddlewareMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:middleware {name : Middleware class name} {--force}';

    protected $description = 'Generate an HTTP middleware in the current package.';

    protected function stubName(): string
    {
        return 'middleware.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Http/Middleware', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Http\\Middleware', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
