<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class HelloCommand extends Command
{
    protected $signature = 'hello
                            {name=world : Who to greet}
                            {--shout : Greet in uppercase}';

    protected $description = 'Print a greeting (sample command).';

    public function handle(): int
    {
        $message = "Hello, {$this->argument('name')}, from Zonda!";

        if ($this->option('shout')) {
            $message = strtoupper($message);
        }

        $this->info($message);

        return self::SUCCESS;
    }
}
