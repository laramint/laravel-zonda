<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class ModelMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:model {name : Model class name} {--force}';

    protected $description = 'Generate an Eloquent model in the current package.';

    protected function stubName(): string
    {
        return 'model.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Models', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Models', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
