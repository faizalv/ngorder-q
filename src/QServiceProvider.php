<?php

namespace Ngorder\Q;

use Illuminate\Support\ServiceProvider;
use Ngorder\Q\Console\QConsume;
use Ngorder\Q\Console\QInstall;
use Ngorder\Q\Factory\Connection;
use Ngorder\Q\Factory\Router;
use Ngorder\Q\Factory\Publisher;
use Ngorder\Q\Factory\Consumer;
use Ngorder\Q\Facades\Message;

class QServiceProvider extends ServiceProvider
{
    protected $routing = [];

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $connection = new Connection($this->getConfig());
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/q.php' => config_path('q.php'),
            ], 'config');
            $this->commands([
                QInstall::class,
                QConsume::class,
            ]);
            $router = new Router($this->routing);
            $this->app->singleton(Consumer::class, function () use ($connection, $router) {
                return new Consumer($connection, $router, $this->getConfig());
            });
        } else {
            $this->app->singleton(Publisher::class, function () use ($connection) {
                return new Publisher($connection);
            });
            $this->setMessageMacro();
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->publishSomething();
    }

    private function getConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/q.php', 'q');
        return $this->app['config']['q'];
    }

    private function publishSomething()
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

    private function setMessageMacro()
    {
        Message::macro('setExchange', function ($name, $type) {
            return app(Publisher::class)->setExchange($name, $type);
        });

        Message::macro('setQueue', function ($name, $arguments) {
            return app(Publisher::class)->setQueue($name, $arguments);
        });

        Message::macro('publish', function ($routing_key, $message) {
            return app(Publisher::class)->publish($routing_key, $message);
        });
    }
}
