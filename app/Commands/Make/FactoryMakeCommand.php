<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class FactoryMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:factory {name : Factory base name (model name without Factory suffix)} {--model= : Model class name (defaults to {name})} {--force}';

    protected $description = 'Generate an Eloquent model factory in the current package.';

    protected function stubName(): string
    {
        return 'factory.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('database/factories', $subNs, $class . 'Factory.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        $model = $this->studly((string) ($this->option('model') ?: $class));
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Database\\Factories', $subNs),
            '{{ClassName}}' => $class,
            '{{ModelClass}}' => $model,
            '{{ModelFqn}}' => $pkg->namespace . '\\Models\\' . $model,
        ];
    }
}
