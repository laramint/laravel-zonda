<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class EventMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:event {name : Event class name} {--force}';

    protected $description = 'Generate an event class in the current package.';

    protected function stubName(): string
    {
        return 'event.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Events', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Events', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
