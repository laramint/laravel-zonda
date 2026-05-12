<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class NotificationMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:notification {name : Notification class name} {--force}';

    protected $description = 'Generate a notification class in the current package.';

    protected function stubName(): string
    {
        return 'notification.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Notifications', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Notifications', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
