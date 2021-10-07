<?php

namespace Ngorder\Q\Amqp;

use Ngorder\Q\Amqp\Contracts\QConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Connection implements QConnection
{
    private array $config;
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(): Connection
    {
        $config = $this->config['connections'][$this->config['connect_to']];

        $this->connection = new AMQPStreamConnection(
            $config['host'] ?? 'localhost',
            $config['port'] ?? 5672,
            $config['user'] ?? 'guest',
            $config['pass'] ?? 'guest',
            $config['vhost'] ?? '/'
        );

        return $this;
    }

    public function openChannel(): void
    {
        $this->channel = $this->connection->channel();
    }

    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }

    public function closeChannel(): void
    {
        $this->channel->close();
    }

    public function reopenChannel(): void
    {
        if (!$this->channel->is_open()) {
            $this->channel = $this->connection->channel();
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}