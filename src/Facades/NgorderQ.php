<?php

namespace Ngorder\Q\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Traits\Macroable;
use Ngorder\Q\Amqp\Helpers\Instances;
use Ngorder\Q\Publisher;

/**
 * @method static void startFake()
 * @method static void stopFake()
 */
class NgorderQ extends Facade
{
    use Macroable;

    /**
     * @param int $minutes
     * @return Publisher
     */
    public static function delay(int $minutes): Publisher
    {
        return Instances::getPublisher()->delay($minutes);
    }

    /**
     * @param string $routing_key
     * @param array|string $message
     */
    public static function publish(string $routing_key, $message)
    {
        Instances::getPublisher()->publish($routing_key, $message);
    }
}