<?php

use App\Sandbox\PackageLinker;

class TestablePackageLinker extends PackageLinker
{
    public array $composerCalls = [];

    protected function runComposer(array $cmd): void
    {
        $this->composerCalls[] = $cmd;
    }
}
