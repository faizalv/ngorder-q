<?php

namespace Ngorder\Q\Amqp;

use Ngorder\Q\Amqp\Contracts\QConnection;
use Ngorder\Q\Amqp\Contracts\QContext;
use Ngorder\Q\Amqp\Helpers\Help;
use PhpAmqpLib\Channel\AMQPChannel;

class Context implements QContext
{
    private QConnection $connection;
    private AMQPChannel $channel;
    private ?string $exchange_name;
    private ?string $queue_name;
    private string $routing_key;

    public function __construct(QConnection $connection, string $channel_tag)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->getChannel($channel_tag);
    }

    public function setRoutingKey(string $routing_key): Context
    {
        $this->routing_key = $routing_key;
        return $this;
    }

    public function makeQueue(?string $queue_name, ?array $queue_args): Context
    {
        $config = $this->connection->getConfig();
        $this->queue_name = Help::createQName($config, $queue_name, $this->routing_key);
        $queue_args = Help::parseQArguments($config, $queue_args);

        $this->channel->queue_declare(
            $this->queue_name,
            self::Q_PASSIVE,
            self::Q_DURABLE,
            self::Q_EXCLUSIVE,
            self::Q_AUTO_DELETE,
            self::Q_NOWAIT,
            $queue_args
        );

        return $this;
    }

    public function makeExchange(?string $exchange_name, ?string $exchange_type): Context
    {
        if (empty($exchange_name) || empty($exchange_type)) {
            $exchange = $this->connection->getConfig()['exchange'];
            $exchange_name = $exchange['name'];
            $exchange_type = $exchange['type'];
        }

        $this->channel->exchange_declare(
            $exchange_name,
            $exchange_type,
            self::E_PASSIVE,
            self::E_DURABLE,
            self::E_AUTO_DELETE,
            self::E_INTERNAL,
            self::E_NOWAIT
        );

        $this->exchange_name = $exchange_name;

        return $this;
    }

    public function getExchangeName(): string
    {
        return $this->exchange_name;
    }

    public function getQueueName(): string
    {
        return $this->queue_name;
    }

    public function getRoutingKey(): string
    {
        return $this->routing_key;
    }

    public function bind(): void
    {
        $this->channel->queue_bind(
            $this->queue_name,
            $this->exchange_name,
            $this->routing_key
        );
    }
}