<?php

namespace App\Package;

use RuntimeException;

class PackageScaffolder
{
    private const TESTBENCH_MATRIX = [
        9 => '^7.0',
        10 => '^8.0',
        11 => '^9.0',
        12 => '^10.0',
        13 => '^11.0',
    ];

    private string $stubsDir;

    public function __construct(?string $stubsDir = null)
    {
        $this->stubsDir = $stubsDir ?? base_path('app/Stubs/package');
    }

    /**
     * @param int|list<int> $laravelMajors  Either a single major or a list (e.g. [10, 11, 12]).
     * @return array{root: string, namespace: string, providerClass: string, laravelMajors: list<int>}
     */
    public function scaffold(string $vendor, string $name, string $targetDir, int|array $laravelMajors = [12]): array
    {
        $majors = $this->normalizeMajors($laravelMajors);

        if (is_dir($targetDir) && (new \FilesystemIterator($targetDir))->valid()) {
            throw new RuntimeException("Target directory is not empty: {$targetDir}");
        }

        $vendorStudly = $this->studly($vendor);
        $nameStudly = $this->studly($name);
        $namespace = "{$vendorStudly}\\{$nameStudly}";
        $providerClass = "{$nameStudly}ServiceProvider";

        $replacements = [
            '{{vendor}}' => $vendor,
            '{{name}}' => $name,
            '{{Vendor}}' => $vendorStudly,
            '{{Name}}' => $nameStudly,
            '{{Namespace}}' => $namespace,
            '{{ProviderClass}}' => $providerClass,
            '{{shortName}}' => $name,
        ];

        $this->mkdirP($targetDir);
        $this->mkdirP("{$targetDir}/src");
        $this->mkdirP("{$targetDir}/tests");

        file_put_contents(
            "{$targetDir}/composer.json",
            $this->renderComposerJson($vendor, $name, $namespace, $providerClass, $majors)
        );

        $this->renderStub('ServiceProvider.stub', "{$targetDir}/src/{$providerClass}.php", $replacements);
        $this->renderStub('TestCase.stub', "{$targetDir}/tests/TestCase.php", $replacements);
        $this->renderStub('Pest.stub', "{$targetDir}/tests/Pest.php", $replacements);
        $this->renderStub('ExampleTest.stub', "{$targetDir}/tests/ExampleTest.php", $replacements);
        $this->renderStub('phpunit.xml.dist.stub', "{$targetDir}/phpunit.xml.dist", $replacements);
        $this->renderStub('README.md.stub', "{$targetDir}/README.md", $replacements);
        $this->renderStub('gitignore.stub', "{$targetDir}/.gitignore", $replacements);

        return [
            'root' => realpath($targetDir) ?: $targetDir,
            'namespace' => $namespace,
            'providerClass' => $providerClass,
            'laravelMajors' => $majors,
        ];
    }

    /**
     * @param int|list<int> $laravelMajors
     * @return list<int>
     */
    private function normalizeMajors(int|array $laravelMajors): array
    {
        $list = is_int($laravelMajors) ? [$laravelMajors] : $laravelMajors;
        if ($list === []) {
            throw new RuntimeException('At least one Laravel version must be selected.');
        }
        $list = array_values(array_unique(array_map('intval', $list)));
        sort($list);
        foreach ($list as $m) {
            if (! isset(self::TESTBENCH_MATRIX[$m])) {
                throw new RuntimeException("Unsupported Laravel version: {$m}. Supported: " . implode(', ', array_keys(self::TESTBENCH_MATRIX)) . '.');
            }
        }
        return $list;
    }

    /**
     * @param list<int> $majors
     */
    private function renderComposerJson(string $vendor, string $name, string $namespace, string $providerClass, array $majors): string
    {
        $illuminate = implode('|', array_map(fn (int $m) => "^{$m}.0", $majors));
        $testbench = implode('|', array_map(fn (int $m) => self::TESTBENCH_MATRIX[$m], $majors));

        $data = [
            'name' => "{$vendor}/{$name}",
            'description' => "{$this->studly($name)} package.",
            'type' => 'library',
            'license' => 'MIT',
            'require' => [
                'php' => '^8.2',
                'illuminate/support' => $illuminate,
            ],
            'require-dev' => [
                'orchestra/testbench' => $testbench,
                'pestphp/pest' => '^3.0|^4.0',
            ],
            'autoload' => [
                'psr-4' => [
                    "{$namespace}\\" => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    "{$namespace}\\Tests\\" => 'tests/',
                ],
            ],
            'extra' => [
                'zonda' => [
                    'package' => true,
                    'laravel' => $majors,
                ],
                'laravel' => [
                    'providers' => ["{$namespace}\\{$providerClass}"],
                ],
            ],
            'config' => [
                'allow-plugins' => ['pestphp/pest-plugin' => true],
            ],
            'minimum-stability' => 'stable',
            'prefer-stable' => true,
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private function renderStub(string $stub, string $dest, array $replacements): void
    {
        $path = $this->stubsDir . DIRECTORY_SEPARATOR . $stub;
        if (! is_file($path)) {
            throw new RuntimeException("Stub not found: {$path}");
        }
        $content = strtr((string) file_get_contents($path), $replacements);
        $this->mkdirP(dirname($dest));
        file_put_contents($dest, $content);
    }

    private function mkdirP(string $dir): void
    {
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: {$dir}");
        }
    }

    private function studly(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? '';
        return str_replace(' ', '', ucwords(strtolower($value)));
    }
}
