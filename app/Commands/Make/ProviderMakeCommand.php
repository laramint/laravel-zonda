<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class ProviderMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:provider {name : Provider class name} {--force}';

    protected $description = 'Generate a service provider in the current package.';

    protected function stubName(): string
    {
        return 'provider.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Providers', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Providers', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
