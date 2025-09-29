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
        $package = Str::lower($this->argument('package'));     // ecommerce
        $studly  = Str::studly($package);                      // Ecommerce
        $name    = Str::studly($this->argument('name'));       // CustomerFactory (or UserFactory)

        $basePath     = base_path("packages/{$package}/src");
        $factoryDir   = "{$basePath}/databases/factories";
        $factoryFile  = "{$factoryDir}/{$name}.php";

        // 🔹 Ensure directory exists
        if (!File::exists($factoryDir)) {
            File::makeDirectory($factoryDir, 0755, true);
            $this->info("📁 Created directory: {$factoryDir}");
        }

        // 🔹 Prevent overwriting
        if (File::exists($factoryFile)) {
            $this->error("❌ Factory {$name} already exists in {$factoryDir}");
            return self::FAILURE;
        }

        // 🔹 Write the factory stub with correct namespace
        File::put($factoryFile, $this->getFactoryStub($studly, $name));

        $this->info("✅ Factory created at {$factoryFile}");
        return self::SUCCESS;
    }

    /**
     * Build the factory file contents.
     */
    protected function getFactoryStub(string $package, string $name): string
    {
        // Extract model name from factory name (UserFactory -> User)
        $model = Str::replaceLast('Factory', '', $name);

        return <<<PHP
<?php

namespace {$package}\\Factories;

use Illuminate\\Database\\Eloquent\\Factories\\Factory;
use Illuminate\\Support\\Facades\\Hash;
use Illuminate\\Support\\Str;

/**
 * @extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<\\{$package}\\App\\Models\\{$model}>
 */
class {$name} extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string \$password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'name' => fake()->name(),
            // 'email' => fake()->unique()->safeEmail(),
            // 'email_verified_at' => now(),
            // 'password' => static::\$password ??= Hash::make('password'),
            // 'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return \$this->state(fn (array \$attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
PHP;
    }
}
