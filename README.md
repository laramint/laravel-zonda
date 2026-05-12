# Zonda

A PHP CLI tool built with [Laravel Zero](https://laravel-zero.com/). Invoke as `zonda [options] [arguments]` from anywhere in your terminal.

## Requirements

- PHP `^8.2`
- Composer (only required for source / global install)

## Install

### Option A — Composer global

```bash
composer global require laramint/laravel-zonda
```

Make sure Composer's global `bin` directory is on your `PATH`. On macOS/Linux that's usually one of:

- `~/.composer/vendor/bin`
- `~/.config/composer/vendor/bin`

Then:

```bash
zonda --help
```

### Option B — PHAR

Grab `zonda` from the [GitHub releases](https://github.com/laramint/laravel-zonda/releases) page (or build it yourself — see below), then:

```bash
chmod +x zonda
sudo mv zonda /usr/local/bin/zonda
zonda --help
```

## Usage

```bash
zonda                       # list available commands
zonda hello                  # → Hello, world, from Zonda!
zonda hello Alice            # → Hello, Alice, from Zonda!
zonda hello Alice --shout    # → HELLO, ALICE, FROM ZONDA!
zonda --version
```

## Adding your own commands

Create a class under `app/Commands/` extending `LaravelZero\Framework\Commands\Command`. Laravel Zero auto-discovers commands in `app/Commands/`.

```php
namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class MakeThingCommand extends Command
{
    protected $signature = 'make:thing {name} {--force}';
    protected $description = 'Make a thing.';

    public function handle(): int
    {
        $this->info("Making {$this->argument('name')}");
        return self::SUCCESS;
    }
}
```

The signature DSL is the same as Laravel's Artisan — see [the docs](https://laravel.com/docs/artisan#defining-input-expectations).

## Build the PHAR

```bash
php zonda app:build zonda
# → builds/zonda
```

## Development

```bash
git clone https://github.com/laramint/laravel-zonda.git
cd laravel-zonda
composer install
php zonda hello
./vendor/bin/pest
```

## License

MIT
