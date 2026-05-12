<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class ListenerMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:listener {name : Listener class name} {--event= : Event class to bind (short name or FQN)} {--force}';

    protected $description = 'Generate an event listener in the current package.';

    protected function stubName(): string
    {
        return 'listener.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('src/Listeners', $subNs, $class . '.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);

        $eventOpt = (string) ($this->option('event') ?: 'Event');
        if (str_contains($eventOpt, '\\')) {
            $eventFqn = ltrim($eventOpt, '\\');
            $eventClass = substr($eventFqn, strrpos($eventFqn, '\\') + 1);
        } else {
            $eventClass = $this->studly($eventOpt);
            $eventFqn = $pkg->namespace . '\\Events\\' . $eventClass;
        }

        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Listeners', $subNs),
            '{{ClassName}}' => $class,
            '{{EventClass}}' => $eventClass,
            '{{EventFqn}}' => $eventFqn,
        ];
    }
}
