<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class ControllerMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:controller {name : Controller class name} {--force}';

    protected $description = 'Generate a controller in the current package.';

    protected function stubName(): string
    {
        return 'controller.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Http/Controllers', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Http\\Controllers', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
