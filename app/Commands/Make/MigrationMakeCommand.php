<?php

namespace App\Commands\Make;

use App\Package\PackageContext;
use App\Support\AbstractMakeCommand;

class MigrationMakeCommand extends AbstractMakeCommand
{
    protected $signature = 'make:migration {name : Migration name (e.g. create_widgets_table)} {--force}';

    protected $description = 'Generate a migration in the current package.';

    private string $timestamp;

    public function __construct()
    {
        parent::__construct();
        $this->timestamp = date('Y_m_d_His');
    }

    protected function stubName(): string
    {
        return 'migration.stub';
    }

    protected function relativeTargetPath(PackageContext $pkg, string $name): string
    {
        $snake = $this->snake($name);
        return "database/migrations/{$this->timestamp}_{$snake}.php";
    }

    protected function replacements(PackageContext $pkg, string $name): array
    {
        return ['{{table}}' => $this->guessTable($name)];
    }

    private function snake(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value) ?? $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', '_', $value) ?? $value;
        return strtolower(trim($value, '_'));
    }

    private function guessTable(string $name): string
    {
        $snake = $this->snake($name);
        if (preg_match('/^create_(.+)_table$/', $snake, $m)) {
            return $m[1];
        }
        return $snake;
    }
}
