<?php

namespace Ngorder\Q\Amqp\Helpers;

use Ngorder\Q\Amqp\Contracts\QConnection;
use Ngorder\Q\Amqp\Router;
use Ngorder\Q\Publisher;

class Instances
{
    public static function getConnection(): QConnection
    {
        return app(QConnection::class);
    }

    public static function getPublisher(): Publisher
    {
        return app(Publisher::class);
    }

    public static function getRouter(): Router
    {
        return app(Router::class);
    }
}