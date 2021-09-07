<?php

namespace Ngorder\Q\Factory;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Publisher
{
    private $connection, $exchange_name, $exchange_type, $queue_name, $queue_args;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection->connect();
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

    public function publish($routing_key, $message)
    {
        $this->connection->reopenChannel();
        $connection = $this->connection->setRoute($routing_key)
            ->makeExchange($this->exchange_name, $this->exchange_type)
            ->makeQueue($this->queue_name, $this->queue_args)
            ->bind();
        if (is_array($message)) {
            $headers = new AMQPTable([
                'Content-type' => 'application/json'
            ]);
            $message = json_encode($message);

        } else {
            $headers = new AMQPTable([
                'Content-type' => 'text/plain'
            ]);
        }
        $message = new AMQPMessage($message);
        $message->set('application_headers', $headers);
        $connection->getChannel()->basic_publish($message, $connection->getExchangeName(), $routing_key);
        $connection->getChannel()->close();
    }
}
