<?php

namespace Ngorder\Q\Factory;

use Exception;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer
{
    private $connection, $channel, $router, $routing_key, $consumer, $exchange_name, $exchange_type, $queue_name, $queue_args;

    public function __construct(Connection $connection, Router $router, array $config)
    {
        $this->connection = $connection;
        $this->router = $router;
    }

    public function connect() {
        $this->connection->connect();
        $this->channel = $this->connection->getChannel();
    }

    /**
     * @throws Exception
     */
    public function prepare(string $routing_key)
    {
        $this->routing_key = $routing_key;
        if ($this->router->checkRoutingKey($routing_key)) {
            $this->consumer = $this->router->getConsumer($routing_key);
        } else {
            throw new Exception("Unknown routing key: [$routing_key] Be sure to register the routing key in QServiceProvider");
        }
    }

    public function setExchange($name, $type)
    {
        $this->exchange_name = $name;
        $this->exchange_type = $type;
        return $this;
    }

    public function setQueue($name, $arguments)
    {
        $this->queue_name = $name;
        $this->queue_args = $arguments;
        return $this;
    }

    public function work()
    {
        $connection = $this->connection->setRoute($this->routing_key)
            ->makeExchange($this->exchange_name, $this->exchange_type)
            ->makeQueue($this->queue_name, $this->queue_args)
            ->bind();
        $connection->getChannel()->basic_consume($connection->getQName(), '', false, true, false, false, function (AMQPMessage $bytes) {
            $properties = $bytes->get_properties();
            $content_type = $properties['application_headers']['Content-type'] ?? null;
            $message = $content_type === 'application/json' ? json_decode($bytes->body, true) : $bytes->body;

            if (is_array($this->consumer)){
                $consumer = [new $this->consumer[0](), $this->consumer[1]];
                call_user_func($consumer, $message);
            } else {
                call_user_func(new $this->consumer(), $message);
            }
            info("QConsume [". $this->routing_key ."] message handled");
        });

        while(true) {
            $this->channel->wait();
        }
    }
}