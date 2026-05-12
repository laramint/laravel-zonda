<?php

namespace App\Sandbox;

class SandboxManagerFactory
{
    /** @var array<int, SandboxManager> */
    private array $cache = [];

    public function __construct(private readonly ?string $homeDir = null) {}

    public function for(int $major): SandboxManager
    {
        return $this->cache[$major] ??= new SandboxManager($major, $this->homeDir);
    }
}
