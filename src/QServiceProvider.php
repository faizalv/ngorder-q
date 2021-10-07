<?php

namespace Ngorder\Q;

use Illuminate\Support\ServiceProvider;
use Ngorder\Q\Amqp\Contracts\QConnection;
use Ngorder\Q\Amqp\Connection;
use Ngorder\Q\Amqp\Helpers\Instances;
use Ngorder\Q\Amqp\Router;
use Ngorder\Q\Console\ConsumeCommand;
use Ngorder\Q\Console\InstallCommand;
use Ngorder\Q\Facades\Message;
use Ngorder\Q\Mocker\Factory as Mocker;

class QServiceProvider extends ServiceProvider
{
    protected array $routing = [];

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->app->singleton(QConnection::class, function () {
            return new Connection($this->getConfig());
        });

        $this->app->singleton(Publisher::class, function () {
            return new Publisher(Instances::getConnection());
        });

        $this->app->singleton(Router::class, function () {
            return new Router($this->routing);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/q.php' => config_path('q.php'),
            ], 'config');
            $this->commands([
                InstallCommand::class,
                ConsumeCommand::class,
            ]);

            Message::macro('startFake', function () {
                Mocker::startFake();
            });

            Message::macro('stopFake', function () {
                Mocker::stopFake();
            });
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->publishEssentials();
    }

    private function getConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/q.php', 'q');
        return $this->app['config']['q'];
    }

    private function publishEssentials()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/q.php' => $this->app->configPath('q.php'),
            ], 'ngorder-q-config');
            $provider = 'QServiceProvider';
            $this->publishes([
                __DIR__ . "/../stubs/$provider.stub" => $this->app->path("Providers/$provider.php"),
            ], 'ngorder-q-provider');
        }
    }
}
