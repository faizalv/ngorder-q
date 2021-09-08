<?php

namespace Ngorder\Q\Factory;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Publisher
{
    private $connection, $exchange_name, $exchange_type, $queue_name, $queue_args, $delay;

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

    public function delay($minutes)
    {
        $this->delay = $minutes;
        return $this;
    }

    public function publish($routing_key, $message)
    {
        $this->connection->reopenChannel();
        $connection = $this->bind($routing_key);
        if ($this->delay > 0) {
            $this->prepareDelay($routing_key);
            $connection = $this->bind($routing_key);
        }
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

    private function prepareDelay($routing_key)
    {
        if (empty($this->queue_name)) {
            $this->queue_name = $routing_key . '.delayer';
        } else {
            if (!str_contains($this->queue_name, '.delayer')) {
                $this->queue_name = $this->queue_name . '.delayer';
            }
        }
        $active_config = $this->connection->getActiveConfig();
        $default_exchange_name = $active_config['exchange']['name'];
        if (empty($this->exchange_name)) {
            $this->exchange_name = $default_exchange_name . '.delayer';
            $this->queue_args['x-dead-letter-exchange'] = $default_exchange_name;
        } else {
            if (!str_contains($this->exchange_name, '.delayer')){
                $this->queue_args['x-dead-letter-exchange'] = $this->exchange_name;
                $this->exchange_name = $this->exchange_name . '.delayer';
            } else {
                $this->queue_args['x-dead-letter-exchange'] = preg_replace('/(\.delayer)/', '', $this->exchange_name);
            }
        }
        $this->exchange_type = $active_config['exchange']['type'];
        $this->queue_args['x-message-ttl'] = (int) $this->delay * 60000;
    }

    private function bind($routing_key): Connection
    {
        return $this->connection->setRoute($routing_key)
            ->makeExchange($this->exchange_name, $this->exchange_type)
            ->makeQueue($this->queue_name, $this->queue_args)
            ->bind();
    }
}
