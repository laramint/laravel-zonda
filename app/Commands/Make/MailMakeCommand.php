<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class MailMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:mail {name : Mailable class name} {--force}';

    protected $description = 'Generate a mailable class in the current package.';

    protected function stubName(): string
    {
        return 'mail.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Mail', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Mail', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
