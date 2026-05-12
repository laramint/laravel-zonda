<?php

use App\Package\PackageScaffolder;

beforeEach(function () {
    $this->tmp = sys_get_temp_dir() . '/zonda-make-' . uniqid();
    mkdir($this->tmp, 0755, true);
    (new PackageScaffolder())->scaffold('acme', 'widget', $this->tmp . '/widget', [12]);
    $this->pkg = $this->tmp . '/widget';
    $this->originalCwd = getcwd();
    chdir($this->pkg);
});

afterEach(function () {
    chdir($this->originalCwd);
    rrmdir($this->tmp);
});

it('generates a command class', function () {
    $this->artisan('make:command', ['name' => 'SayHello'])->assertExitCode(0);

    $file = $this->pkg . '/src/Console/Commands/SayHello.php';
    expect(is_file($file))->toBeTrue();
    $content = file_get_contents($file);
    expect($content)->toContain('namespace Acme\\Widget\\Console\\Commands;')
        ->and($content)->toContain('class SayHello extends Command')
        ->and($content)->toContain("protected \$signature = 'say-hello'");
});

it('generates a model class', function () {
    $this->artisan('make:model', ['name' => 'Widget'])->assertExitCode(0);

    $file = $this->pkg . '/src/Models/Widget.php';
    expect(is_file($file))->toBeTrue();
    expect(file_get_contents($file))->toContain('namespace Acme\\Widget\\Models;');
});

it('generates a model in a subfolder', function () {
    $this->artisan('make:model', ['name' => 'Blog/Post'])->assertExitCode(0);

    $file = $this->pkg . '/src/Models/Blog/Post.php';
    expect(is_file($file))->toBeTrue();
    $content = file_get_contents($file);
    expect($content)->toContain('namespace Acme\\Widget\\Models\\Blog;')
        ->and($content)->toContain('class Post extends Model');
});

it('generates a controller class', function () {
    $this->artisan('make:controller', ['name' => 'WidgetController'])->assertExitCode(0);

    $file = $this->pkg . '/src/Http/Controllers/WidgetController.php';
    expect(is_file($file))->toBeTrue();
    expect(file_get_contents($file))->toContain('namespace Acme\\Widget\\Http\\Controllers;');
});

it('generates a provider class', function () {
    $this->artisan('make:provider', ['name' => 'RouteServiceProvider'])->assertExitCode(0);

    $file = $this->pkg . '/src/Providers/RouteServiceProvider.php';
    expect(is_file($file))->toBeTrue();
    expect(file_get_contents($file))->toContain('namespace Acme\\Widget\\Providers;');
});

it('generates a migration with the right table name', function () {
    $this->artisan('make:migration', ['name' => 'create_widgets_table'])->assertExitCode(0);

    $files = glob($this->pkg . '/database/migrations/*_create_widgets_table.php');
    expect($files)->toHaveCount(1);
    expect(file_get_contents($files[0]))->toContain("Schema::create('widgets'");
});

it('refuses to overwrite an existing file without --force', function () {
    $this->artisan('make:command', ['name' => 'SayHello'])->assertExitCode(0);
    $this->artisan('make:command', ['name' => 'SayHello'])->assertExitCode(1);
});

it('fails clearly when not inside a package', function () {
    chdir($this->tmp); // not a package dir
    $this->artisan('make:command', ['name' => 'SayHello'])->assertExitCode(1);
});

dataset('makeCases', [
    'request' => ['make:request', 'StorePost', 'src/Http/Requests/StorePost.php', 'Acme\\Widget\\Http\\Requests', 'class StorePost extends FormRequest'],
    'resource' => ['make:resource', 'PostResource', 'src/Http/Resources/PostResource.php', 'Acme\\Widget\\Http\\Resources', 'class PostResource extends JsonResource'],
    'seeder' => ['make:seeder', 'Database', 'database/seeders/DatabaseSeeder.php', 'Acme\\Widget\\Database\\Seeders', 'class DatabaseSeeder extends Seeder'],
    'middleware' => ['make:middleware', 'Authenticate', 'src/Http/Middleware/Authenticate.php', 'Acme\\Widget\\Http\\Middleware', 'class Authenticate'],
    'job' => ['make:job', 'ProcessThing', 'src/Jobs/ProcessThing.php', 'Acme\\Widget\\Jobs', 'class ProcessThing implements ShouldQueue'],
    'event' => ['make:event', 'Pinged', 'src/Events/Pinged.php', 'Acme\\Widget\\Events', 'class Pinged'],
    'mail' => ['make:mail', 'Welcome', 'src/Mail/Welcome.php', 'Acme\\Widget\\Mail', 'class Welcome extends Mailable'],
    'notification' => ['make:notification', 'Verify', 'src/Notifications/Verify.php', 'Acme\\Widget\\Notifications', 'class Verify extends Notification'],
]);

it('generates {0} via {0} {1}', function (string $command, string $name, string $relPath, string $namespace, string $classLine) {
    $this->artisan($command, ['name' => $name])->assertExitCode(0);

    $file = $this->pkg . '/' . $relPath;
    expect(is_file($file))->toBeTrue();
    $content = file_get_contents($file);
    expect($content)->toContain("namespace {$namespace};")
        ->and($content)->toContain($classLine);
})->with('makeCases');

it('generates a factory referencing the model FQN', function () {
    $this->artisan('make:factory', ['name' => 'Post', '--model' => 'Post'])->assertExitCode(0);

    $file = $this->pkg . '/database/factories/PostFactory.php';
    expect(is_file($file))->toBeTrue();
    $content = file_get_contents($file);
    expect($content)->toContain('namespace Acme\\Widget\\Database\\Factories;')
        ->and($content)->toContain('use Acme\\Widget\\Models\\Post;')
        ->and($content)->toContain('protected $model = Post::class;');
});

it('generates a listener for an event in the package', function () {
    $this->artisan('make:listener', ['name' => 'SendMail', '--event' => 'Pinged'])->assertExitCode(0);

    $file = $this->pkg . '/src/Listeners/SendMail.php';
    expect(is_file($file))->toBeTrue();
    $content = file_get_contents($file);
    expect($content)->toContain('namespace Acme\\Widget\\Listeners;')
        ->and($content)->toContain('use Acme\\Widget\\Events\\Pinged;')
        ->and($content)->toContain('public function handle(Pinged $event)');
});

it('generates a policy referencing the guarded model', function () {
    $this->artisan('make:policy', ['name' => 'PostPolicy', '--model' => 'Post'])->assertExitCode(0);

    $file = $this->pkg . '/src/Policies/PostPolicy.php';
    expect(is_file($file))->toBeTrue();
    expect(file_get_contents($file))->toContain('namespace Acme\\Widget\\Policies;');
});

it('generates a Pest feature test by default', function () {
    $this->artisan('make:test', ['name' => 'Smoke'])->assertExitCode(0);

    $file = $this->pkg . '/tests/Feature/SmokeTest.php';
    expect(is_file($file))->toBeTrue();
    expect(file_get_contents($file))->toContain("it('smoke'");
});

it('generates a Blade view at the right path', function () {
    $this->artisan('make:view', ['name' => 'admin.users.index'])->assertExitCode(0);

    $file = $this->pkg . '/resources/views/admin/users/index.blade.php';
    expect(is_file($file))->toBeTrue();
    $content = file_get_contents($file);
    expect($content)->toContain('<title>Index</title>')
        ->and($content)->toContain('<h1>Index</h1>');
});

it('accepts slash-separated view names', function () {
    $this->artisan('make:view', ['name' => 'mail/welcome'])->assertExitCode(0);
    expect(is_file($this->pkg . '/resources/views/mail/welcome.blade.php'))->toBeTrue();
});

it('generates a config file named after the package by default', function () {
    $this->artisan('make:config')->assertExitCode(0);

    $file = $this->pkg . '/config/widget.php';
    expect(is_file($file))->toBeTrue();
    expect(file_get_contents($file))->toContain('return [');
});

it('generates a named config file', function () {
    $this->artisan('make:config', ['name' => 'services'])->assertExitCode(0);
    expect(is_file($this->pkg . '/config/services.php'))->toBeTrue();
});

it('generates a Pest unit test when --unit is passed', function () {
    $this->artisan('make:test', ['name' => 'Util', '--unit' => true])->assertExitCode(0);

    expect(is_file($this->pkg . '/tests/Unit/UtilTest.php'))->toBeTrue()
        ->and(is_file($this->pkg . '/tests/Feature/UtilTest.php'))->toBeFalse();
});
