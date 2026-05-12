<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Phar;
use RuntimeException;

class SelfUpdateCommand extends Command
{
    private const RELEASES_API = 'https://api.github.com/repos/laramint/laravel-zonda/releases/latest';
    private const ASSET_NAME = 'zonda';

    protected $signature = 'self-update
                            {--check : Print current/latest versions without downloading}
                            {--force : Re-download even if already on the latest version}';

    protected $description = 'Update the Zonda PHAR to the latest GitHub release.';

    protected function configure(): void
    {
        parent::configure();
        $this->setAliases(['update']);
    }

    public function handle(): int
    {
        $pharPath = Phar::running(false);
        if ($pharPath === '') {
            $this->error('`zonda self-update` only works when Zonda is installed as a PHAR.');
            $this->line('If you installed via Composer, update with:');
            $this->line('  <info>composer global update laramint/laravel-zonda -W</info>');
            return self::FAILURE;
        }

        $current = ltrim((string) config('app.version'), 'v');

        try {
            $release = $this->fetchLatestRelease();
        } catch (RuntimeException $e) {
            $this->error('Could not contact GitHub: ' . $e->getMessage());
            return self::FAILURE;
        }

        $tag = (string) ($release['tag_name'] ?? '');
        if ($tag === '') {
            $this->error('GitHub did not return a release tag.');
            return self::FAILURE;
        }
        $latest = ltrim($tag, 'v');

        $this->line("  current: <comment>{$current}</comment>");
        $this->line("  latest:  <comment>{$latest}</comment>");

        $upToDate = version_compare($current, $latest, '>=');

        if ($this->option('check')) {
            $this->info($upToDate ? 'Already on the latest version.' : "Update available: {$current} → {$latest}");
            return self::SUCCESS;
        }

        if ($upToDate && ! $this->option('force')) {
            $this->info('Already on the latest version. Use --force to re-download.');
            return self::SUCCESS;
        }

        $assetUrl = $this->findAssetUrl($release);
        if ($assetUrl === null) {
            $this->error("Release {$tag} has no '" . self::ASSET_NAME . "' asset attached. Aborting.");
            return self::FAILURE;
        }

        $tmp = $pharPath . '.new';
        $this->info("Downloading {$tag}...");
        try {
            $this->download($assetUrl, $tmp);
        } catch (RuntimeException $e) {
            @unlink($tmp);
            $this->error('Download failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $this->looksLikePhar($tmp)) {
            @unlink($tmp);
            $this->error('Downloaded file does not look like a Zonda PHAR. Aborting.');
            return self::FAILURE;
        }

        @chmod($tmp, 0755);

        if (! @rename($tmp, $pharPath)) {
            @unlink($tmp);
            $this->error("Could not replace {$pharPath} (permission denied?).");
            $this->line('Try again with elevated privileges:');
            $this->line("  <info>sudo zonda self-update</info>");
            return self::FAILURE;
        }

        $this->info("Updated Zonda to {$tag}.");
        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchLatestRelease(): array
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: zonda-self-update\r\nAccept: application/vnd.github+json\r\n",
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents(self::RELEASES_API, false, $ctx);
        if ($body === false) {
            throw new RuntimeException('HTTP request failed.');
        }
        $status = $this->statusFromHeaders($http_response_header ?? []);
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("HTTP {$status} from GitHub API.");
        }

        $data = json_decode($body, true);
        if (! is_array($data)) {
            throw new RuntimeException('Invalid JSON response.');
        }
        return $data;
    }

    /**
     * @param array<string, mixed> $release
     */
    private function findAssetUrl(array $release): ?string
    {
        foreach (($release['assets'] ?? []) as $asset) {
            if (is_array($asset) && ($asset['name'] ?? null) === self::ASSET_NAME) {
                $url = $asset['browser_download_url'] ?? null;
                return is_string($url) ? $url : null;
            }
        }
        return null;
    }

    private function download(string $url, string $destination): void
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: zonda-self-update\r\n",
                'follow_location' => 1,
                'max_redirects' => 5,
                'timeout' => 120,
            ],
        ]);

        $src = @fopen($url, 'rb', false, $ctx);
        if (! $src) {
            throw new RuntimeException("Could not open {$url}");
        }
        $dst = @fopen($destination, 'wb');
        if (! $dst) {
            fclose($src);
            throw new RuntimeException("Could not write to {$destination}");
        }
        $bytes = @stream_copy_to_stream($src, $dst);
        fclose($src);
        fclose($dst);
        if ($bytes === false || $bytes < 1024) {
            throw new RuntimeException('No (or too little) data received.');
        }
    }

    private function looksLikePhar(string $path): bool
    {
        if (! is_file($path) || filesize($path) < 1024 * 1024) {
            return false;
        }
        $head = @file_get_contents($path, false, null, 0, 256);
        if ($head === false) {
            return false;
        }
        return str_starts_with($head, '#!/usr/bin/env php') || str_contains($head, '<?php');
    }

    /**
     * @param list<string> $headers
     */
    private function statusFromHeaders(array $headers): int
    {
        foreach ($headers as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                return (int) $m[1];
            }
        }
        return 0;
    }
}
