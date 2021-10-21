<?php

namespace Ngorder\Q;

use Ngorder\Q\Amqp\Context;
use Ngorder\Q\Amqp\Contracts\QConnection;
use Ngorder\Q\Amqp\Contracts\QContext;
use Ngorder\Q\Amqp\Helpers\Instances;
use Ngorder\Q\Amqp\Message;
use Ngorder\Q\Amqp\Router;

class Publisher
{
    private QConnection $connection;
    private QContext $context;
    private ?string $exchange_name = null;
    private ?string $exchange_type = null;
    private ?string $queue_name = null;
    private ?array $queue_args = null;
    private int $delay = 0;

    public function __construct(QConnection $connection)
    {
        $this->connection = $connection;
        $this->connection->connect();
        $this->connection->openChannel('publish');
    }

    public function setExchange(string $exchange_name, string $exchange_type): Publisher
    {
        $this->exchange_name = $exchange_name;
        $this->exchange_type = $exchange_type;
        return $this;
    }

    public function setQueue($name, $arguments): Publisher
    {
        $this->queue_name = $name;
        $this->queue_args = $arguments;
        return $this;
    }

    public function delay($minutes): Publisher
    {
        $this->delay = $minutes;
        return $this;
    }

    public function publish($routing_key, $message): void
    {
        $this->createContext($routing_key);
        if ($this->delay > 0) {
            $this->prepareDelay($routing_key);
            $this->createContext($routing_key);
        }
        $message = new Message($message);
        Instances::getRouter()->send(
            $message,
            $this->connection,
            $this->context
        );
    }

    private function prepareDelay($routing_key)
    {
        if (empty($this->queue_name)) {
            $this->queue_name = $routing_key . '.delayer';
        } elseif (strpos($this->queue_name, '.delayer') === false) {
            $this->queue_name = $this->queue_name . '.delayer';
        }
        $active_config = $this->connection->getConfig();
        $default_exchange_name = $active_config['exchange']['name'];
        if (empty($this->exchange_name)) {
            $this->exchange_name = $default_exchange_name . '.delayer';
            $this->queue_args['x-dead-letter-exchange'] = $default_exchange_name;
        } elseif (strpos($this->exchange_name, '.delayer') === false) {
            $this->queue_args['x-dead-letter-exchange'] = $this->exchange_name;
            $this->exchange_name = $this->exchange_name . '.delayer';
        } else {
            $this->queue_args['x-dead-letter-exchange'] = preg_replace('/(\.delayer)/', '', $this->exchange_name);
        }
        $this->exchange_type = $active_config['exchange']['type'];
        $this->queue_args['x-message-ttl'] = $this->delay * 60000;
    }

    private function createContext($routing_key): void
    {
        $this->context = new Context($this->connection, 'publish');
        $this->context->setRoutingKey($routing_key)
            ->makeExchange($this->exchange_name, $this->exchange_type)
            ->makeQueue($this->queue_name, $this->queue_args)
            ->bind();
    }
}