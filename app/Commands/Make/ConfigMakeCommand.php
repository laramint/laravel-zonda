<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class ConfigMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:config {name? : Config file name (defaults to the package short name)} {--force}';

    protected $description = 'Generate a config file in the current package (config/<name>.php).';

    protected function stubName(): string
    {
        return 'config.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        return 'config/' . $this->resolveName($pkg, $name) . '.php';
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        return [
            '{{configKey}}' => $this->resolveName($pkg, $name),
        ];
    }

    private function resolveName(PackageContext $pkg, string $name): string
    {
        $candidate = $name !== '' ? $name : $pkg->name;
        return $this->kebab($candidate);
    }

    private function kebab(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $value) ?? $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? $value;
        return strtolower(trim($value, '-'));
    }
}
