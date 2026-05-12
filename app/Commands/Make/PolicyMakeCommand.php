<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class PolicyMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:policy {name : Policy class name} {--model= : Model class name this policy guards} {--force}';

    protected $description = 'Generate a policy class in the current package.';

    protected function stubName(): string
    {
        return 'policy.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Policies', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        $model = $this->studly((string) ($this->option('model') ?: 'Model'));
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Policies', $subNs),
            '{{ClassName}}' => $class,
            '{{ModelClass}}' => $model,
            '{{ModelFqn}}' => $pkg->namespace . '\\Models\\' . $model,
        ];
    }
}
