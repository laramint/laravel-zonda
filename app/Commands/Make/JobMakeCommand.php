<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class JobMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:job {name : Job class name} {--force}';

    protected $description = 'Generate a queueable job in the current package.';

    protected function stubName(): string
    {
        return 'job.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Jobs', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Jobs', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
