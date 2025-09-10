<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class HotswapControllerCommand extends Command
{
    protected $signature = 'hotswap:controller {package} {name} {--r|resource}';
    protected $description = 'Create a controller inside a package';

    public function handle()
    {
        $package = Str::lower($this->argument('package'));    // ecommerce
        $studlyPackage = Str::studly($package);              // Ecommerce
        $name = Str::studly($this->argument('name'));        // Product

        $basePath = base_path("packages/{$package}/src");

        // Controller directory
        $controllerDir = "{$basePath}/app/Http/Controllers";
        if (!File::exists($controllerDir)) File::makeDirectory($controllerDir, 0755, true);

        $controllerFile = "{$controllerDir}/{$name}Controller.php";
        File::put($controllerFile, $this->getControllerStub($studlyPackage, $name, $this->option('resource')));

        $this->info("✅ Controller created at {$controllerFile}");
    }

    protected function getControllerStub($package, $name, $resource)
    {
        $resourceLine = $resource ? "use {$package}\\App\\Models\\{$name};\n" : '';
        $methods = $resource ? $this->getResourceMethods($name) : '';

        return <<<PHP
<?php

namespace {$package}\App\Http\Controllers;

use Illuminate\Http\Request;
{$resourceLine}use App\Http\Controllers\Controller;

class {$name}Controller extends Controller
{
{$methods}
}
PHP;
    }

    protected function getResourceMethods($name)
    {
        // Basic CRUD scaffold
        return <<<PHP

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request \$request)
    {
        //
    }

    public function show({$name} \${$this->camel($name)})
    {
        //
    }

    public function edit({$name} \${$this->camel($name)})
    {
        //
    }

    public function update(Request \$request, {$name} \${$this->camel($name)})
    {
        //
    }

    public function destroy({$name} \${$this->camel($name)})
    {
        //
    }
PHP;
    }

    protected function camel($name)
    {
        return lcfirst($name);
    }
}
