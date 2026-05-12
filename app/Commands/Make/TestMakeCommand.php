<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class TestMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:test {name : Test class name (without "Test" suffix)} {--unit : Place under tests/Unit instead of tests/Feature} {--force}';

    protected $description = 'Generate a Pest test in the current package.';

    protected function stubName(): string
    {
        return 'test.pest.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        [$subNs, $class] = $this->parseName($name);
        $dir = $this->option('unit') ? 'tests/Unit' : 'tests/Feature';
        return $this->targetPath($dir, $subNs, $class . 'Test.php');
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        [, $class] = $this->parseName($name);
        return [
            '{{ClassName}}' => $class,
            '{{TestName}}' => $this->humanize($class),
        ];
    }

    private function humanize(string $value): string
    {
        $spaced = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $value) ?? $value;
        return strtolower(trim($spaced));
    }
}
