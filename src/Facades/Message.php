<?php

namespace Ngorder\Q\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Traits\Macroable;
use Ngorder\Q\Factory\Publisher;

/**
 * @method static Publisher setExchange(string $name, string $type)
 * @method static Publisher setQueue(string $name, array $arguments)
 * @method static Publisher delay(int|float $minutes)
 * @method static void publish(string $routing_key, array|string $message)
 */
class Message extends Facade
{
    use Macroable;
}