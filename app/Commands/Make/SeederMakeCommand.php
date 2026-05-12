<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class SeederMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:seeder {name : Seeder base name (without Seeder suffix)} {--force}';

    protected $description = 'Generate a database seeder in the current package.';

    protected function stubName(): string
    {
        return 'seeder.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        return $this->targetPath('database/seeders', $subNs, $class . 'Seeder.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [$subNs, $class] = $this->parseName($name);
        return [
            '{{Namespace}}' => $this->targetNamespace($pkg, 'Database\\Seeders', $subNs),
            '{{ClassName}}' => $class,
        ];
    }
}
