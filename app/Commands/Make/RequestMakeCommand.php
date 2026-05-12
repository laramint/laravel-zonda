<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class RequestMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:request {name : Form request class name} {--force}';

    protected $description = 'Generate a form request in the current package.';

    protected function stubName(): string
    {
        return 'request.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Http/Requests', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Http\\Requests', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
