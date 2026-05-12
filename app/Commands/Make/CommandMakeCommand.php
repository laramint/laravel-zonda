<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class CommandMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:command {name : Class name (e.g. SayHello)} {--force}';

    protected $description = 'Generate an artisan command class in the current package.';

    protected function stubName(): string
    {
        return 'command.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Console/Commands', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Console\\Commands', $subNs),
            '{{ClassName}}' => $class,
            '{{commandName}}' => $this->kebab($class),
        ];
    }

    private function kebab(string $value): string
    {
        $value = preg_replace('/([a-z])([A-Z])/', '$1-$2', $value) ?? $value;
        return strtolower($value);
    }
}
