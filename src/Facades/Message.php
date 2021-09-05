<?php

namespace Ngorder\Q\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Traits\Macroable;

/**
 * @method static void setExchange(string $name, string $type)
 * @method static void setQueue(string $name, array $arguments)
 * @method static void publish(string $routing_key, array|string $message)
 */
class Message extends Facade
{
    use Macroable;
}