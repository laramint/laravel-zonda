# Zonda

A CLI that lets you build Laravel packages without a host Laravel app. Run `zonda` commands from inside a package directory and Zonda transparently boots a Laravel sandbox, links your package into it, and runs `artisan` against it — so package work feels like working inside a regular Laravel project.

Built on [Laravel Zero](https://laravel-zero.com/).

## How it works

1. `zonda new vendor/name` scaffolds a package skeleton (composer.json, ServiceProvider, Pest tests, etc.) and pins it to a chosen Laravel major (9–13).
2. Inside that package, `zonda make:*` writes files directly into `src/`, `database/`, `resources/`, `tests/` — no sandbox round-trip, fast and offline.
3. `zonda artisan <anything>` lazily creates a sandbox at `~/.zonda/sandboxes/laravel-{N}/` (one per Laravel major), wires the package in via a Composer path repository + symlink, and runs `php artisan` there.
4. `zonda test` runs Pest/PHPUnit inside the package itself against its own `vendor/`.

A package is identified by `extra.zonda.package: true` in its `composer.json`. The pinned Laravel major lives at `extra.zonda.laravel`. Sandboxes are version-keyed, so a Laravel 10 package and a Laravel 13 package coexist with their own installs and don't fight over composer state.

## Requirements

- PHP `^8.2`
- Composer on `PATH` (used to build sandboxes)

## Install

### Composer global

```bash
composer global require laramint/laravel-zonda
```

Make sure Composer's global `bin` is on your `PATH` (usually `~/.composer/vendor/bin` or `~/.config/composer/vendor/bin`), then:

```bash
zonda --help
```

### PHAR

Grab `zonda` from the [GitHub releases](https://github.com/laramint/laravel-zonda/releases) page (or build it yourself — see below):

```bash
chmod +x zonda
sudo mv zonda /usr/local/bin/zonda
```

## Quickstart

```bash
zonda new acme/widget --laravel=12     # scaffold a package pinned to Laravel 12
cd widget
zonda make:command SayHello             # writes src/Console/Commands/SayHello.php
zonda make:model Post                   # writes src/Models/Post.php
zonda make:migration create_posts_table # writes database/migrations/<timestamp>_create_posts_table.php
zonda artisan migrate                   # boots the L12 sandbox, links the package, runs artisan migrate
zonda test                              # runs Pest in the package
```

## Commands

### Top-level

| Command | Purpose |
|---|---|
| `zonda new <vendor/name> [--laravel=N] [--path=...]` | Scaffold a new package. Prompts for the Laravel major if `--laravel` is omitted (supported: 9, 10, 11, 12, 13). |
| `zonda artisan <args...>` | Run `php artisan` inside the version-matched sandbox with the current package linked. The sandbox is created on first use. |
| `zonda test <args...>` | Run the package's own test suite (Pest preferred, PHPUnit fallback). Runs `composer install` in the package on first use. |

### Generators (`make:*`)

All generators are run from inside a package directory and write into the package itself. Most accept a single `{name}` argument and a `--force` flag to overwrite an existing file. Names like `Blog/Post` or `Blog\Post` create subfolders/sub-namespaces.

| Command | Target | Notes |
|---|---|---|
| `make:command <Name>` | `src/Console/Commands/<Name>.php` | Signature defaults to the kebab-cased class name. |
| `make:controller <Name>` | `src/Http/Controllers/<Name>.php` | |
| `make:model <Name>` | `src/Models/<Name>.php` | |
| `make:provider <Name>` | `src/Providers/<Name>.php` | |
| `make:migration <name>` | `database/migrations/<ts>_<name>.php` | Timestamped. Guesses the table name from `create_<table>_table`. |
| `make:request <Name>` | `src/Http/Requests/<Name>.php` | FormRequest with `authorize` + `rules`. |
| `make:resource <Name>` | `src/Http/Resources/<Name>.php` | JsonResource. |
| `make:factory <Name> [--model=]` | `database/factories/<Name>Factory.php` | `--model` controls the FQN in `use` / `$model`. |
| `make:seeder <Name>` | `database/seeders/<Name>Seeder.php` | |
| `make:middleware <Name>` | `src/Http/Middleware/<Name>.php` | |
| `make:job <Name>` | `src/Jobs/<Name>.php` | Implements `ShouldQueue`. |
| `make:event <Name>` | `src/Events/<Name>.php` | Uses `Dispatchable` + `SerializesModels`. |
| `make:listener <Name> [--event=]` | `src/Listeners/<Name>.php` | `--event=Pinged` resolves to `{Namespace}\Events\Pinged`; pass a FQN to point elsewhere. |
| `make:mail <Name>` | `src/Mail/<Name>.php` | Mailable with `envelope`/`content`/`attachments`. |
| `make:notification <Name>` | `src/Notifications/<Name>.php` | |
| `make:policy <Name> [--model=]` | `src/Policies/<Name>.php` | |
| `make:test <Name> [--unit]` | `tests/Feature/<Name>Test.php` (or `tests/Unit/...`) | Pest by default. |
| `make:view <dot.or/slash.name>` | `resources/views/<...>.blade.php` | Both `admin.users.index` and `admin/users/index` work. |
| `make:config [name]` | `config/<name>.php` | `name` defaults to the package's short name, matching what the generated ServiceProvider auto-merges. |

## The generated package

`zonda new` produces a skeleton with:

```
acme/widget/
├── composer.json
├── README.md
├── phpunit.xml.dist
├── src/
│   └── WidgetServiceProvider.php
└── tests/
    ├── ExampleTest.php
    ├── Pest.php
    └── TestCase.php
```

`composer.json` carries the markers Zonda needs:

```json
{
    "extra": {
        "zonda": { "package": true, "laravel": 12 },
        "laravel": { "providers": ["Acme\\Widget\\WidgetServiceProvider"] }
    }
}
```

The pinned Laravel major drives:

- which sandbox runs (`~/.zonda/sandboxes/laravel-12/`)
- the `illuminate/support` constraint (`^12.0`)
- the matching `orchestra/testbench` pin

### Auto-loading ServiceProvider

The generated `ServiceProvider` is conventions-aware: it auto-registers anything it finds on disk, so you can just run `make:view`, `make:config`, `make:migration`, etc. and the loader picks them up without you editing the provider. What gets wired up if it exists:

| Path | What happens |
|---|---|
| `config/<shortName>.php` | `mergeConfigFrom(..., '<shortName>')` in `register()`; publishable as tag `<shortName>-config`. |
| `database/migrations/` | `loadMigrationsFrom(...)`; publishable as tag `<shortName>-migrations`. |
| `resources/views/` | `loadViewsFrom(..., '<shortName>')`; publishable as tag `<shortName>-views`. |
| `resources/lang/` (or `lang/`) | `loadTranslationsFrom(..., '<shortName>')`; publishable as tag `<shortName>-lang`. |
| `routes/web.php` / `routes/api.php` / `routes/console.php` | `loadRoutesFrom(...)` for each that exists. |

`<shortName>` is the package's kebab name (e.g. `acme/widget` → `widget`). The provider uses `dirname(__DIR__)` to find the package root, which is correct because it lives at `src/<ProviderClass>.php`.

## The sandbox

State lives under `~/.zonda/sandboxes/laravel-<N>/`:

```
~/.zonda/sandboxes/
├── laravel-10/         # full Laravel 10 install (only created on first L10 zonda artisan)
│   ├── composer.json   # has a path repo + require pointing at your package
│   └── state.json      # { "linked": "/path/to/the/currently-linked/package" }
├── laravel-12/
└── laravel-13/
```

`zonda artisan ...` is the only command that needs the sandbox; `make:*` and `new` are offline. Linking is cached in `state.json`, so repeated artisan calls from the same package don't re-run `composer update`.

To rebuild from scratch, delete the relevant directory:

```bash
rm -rf ~/.zonda/sandboxes/laravel-12   # forces a fresh L12 sandbox on next zonda artisan
```

## Adding your own top-level commands

Drop a class under `app/Commands/` extending `LaravelZero\Framework\Commands\Command`. Laravel Zero auto-discovers it.

```php
namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class DoThingCommand extends Command
{
    protected $signature = 'do:thing {name} {--force}';
    protected $description = 'Do a thing.';

    public function handle(): int
    {
        $this->info("Doing {$this->argument('name')}");
        return self::SUCCESS;
    }
}
```

For new `make:*` generators, extend `App\Support\AbstractMakeCommand` and implement `stubName()`, `relativeTargetPath()`, and `replacements()`. Use `$this->parseName($name)` for free `Blog/Post` subfolder support, and `$this->targetNamespace(...)` / `$this->targetPath(...)` to compose the namespace and file path.

## Build the PHAR locally

```bash
php zonda app:build zonda --build-version=0.1.0
# → builds/zonda
```

## Development

```bash
git clone https://github.com/laramint/laravel-zonda.git
cd laravel-zonda
composer install
./vendor/bin/pest
```

## License

MIT
