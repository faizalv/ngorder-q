<?php

namespace Ngorder\Q\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = "q:install";
    protected $description = "Install Q";

    public function handle()
    {
        $this->publishAll();
        $this->registerServiceProvider();
        $this->info("Q successfully installed!");
    }

    private function publishAll()
    {
        $this->info("Publishing provider...");
        $this->callSilent('vendor:publish', ['--tag' => 'ngorder-q-provider']);

        $this->info("Publishing configuration...");
        $this->callSilent('vendor:publish', ['--tag' => 'ngorder-q-config']);
    }

    protected function registerServiceProvider()
    {
        $this->info("Registering service provider...");
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());
        $prefix = "{$namespace}\\Providers";
        $config = $this->laravel->configPath('app.php');
        $config_file = file_get_contents($config);
        if (Str::contains($config_file, $prefix . '\\QServiceProvider::class')) {
            return;
        }
        file_put_contents($config,
            str_replace(
                "{$prefix}\\RouteServiceProvider::class," . PHP_EOL,
                "{$prefix}\\RouteServiceProvider::class," . PHP_EOL
                . "        {$prefix}\\QServiceProvider::class," . PHP_EOL,
                $config_file
            )
        );

        file_put_contents($this->laravel->path('Providers/QServiceProvider.php'), str_replace(
            "%namespace%",
            "namespace {$namespace}\Providers;",
            file_get_contents($this->laravel->path('Providers/QServiceProvider.php'))
        ));
    }
}