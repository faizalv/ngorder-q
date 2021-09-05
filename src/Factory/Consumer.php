<?php

namespace Ngorder\Q\Factory;

use Exception;
use Illuminate\Support\Facades\Log;

class Consumer
{
    private $connection, $router, $working_on, $consumer;

    public function __construct(Connection $connection, Router $router)
    {
        $this->connection = $connection;
        $this->router = $router;
    }

    /**
     * @throws Exception
     */
    public function handle(string $routing_key)
    {
        $this->working_on = $routing_key;
        if ($this->router->checkRoutingKey($routing_key)) {
            $this->consumer = $this->router->getConsumer($routing_key);
        }
        throw new Exception('Unknown routing key');
    }

    public function work()
    {
        while(true) {

        }
    }
}