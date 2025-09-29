<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class HotswapFactoryCommand extends Command
{
    protected $signature = 'hotswap:factory {package} {name}';
    protected $description = 'Create a model factory inside a package';

    public function handle(): int
    {
        $package = Str::lower($this->argument('package'));   // ecommerce
        $studly  = Str::studly($package);                    // Ecommerce
        $name    = Str::studly($this->argument('name'));     // Product

        $basePath     = base_path("packages/{$package}/src");
        $factoryDir   = "{$basePath}/databases/factories";
        $factoryFile  = "{$factoryDir}/{$name}Factory.php";

        // 🔹 Ensure directory exists
        if (!File::exists($factoryDir)) {
            File::makeDirectory($factoryDir, 0755, true);
            $this->info("📁 Created directory: {$factoryDir}");
        }

        // 🔹 Prevent overwriting
        if (File::exists($factoryFile)) {
            $this->error("❌ Factory {$name}Factory already exists in {$factoryDir}");
            return self::FAILURE;
        }

        // 🔹 Write the factory stub
        File::put($factoryFile, $this->getFactoryStub($studly, $name));

        $this->info("✅ Factory created at {$factoryFile}");
        return self::SUCCESS;
    }

    /**
     * Build the factory file contents.
     */
    protected function getFactoryStub(string $package, string $model): string
    {
        return <<<PHP
<?php

namespace {$package}\\Factories;

use {$package}\\App\\Models\\{$model};
use Illuminate\\Database\\Eloquent\\Factories\\Factory;

class {$model}Factory extends Factory
{
    protected \$model = {$model}::class;

    public function definition(): array
    {
        return [
            'name'        => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price'       => fake()->randomFloat(2, 5, 500),
            'stock'       => fake()->numberBetween(0, 100),
            'category_id' => null,
            'image_url'   => fake()->imageUrl(400, 400, 'products'),
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
    }
}
PHP;
    }
}
