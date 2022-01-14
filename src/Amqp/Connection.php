<?php

namespace Ngorder\Q\Amqp;

use Ngorder\Q\Amqp\Contracts\QConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Connection implements QConnection
{
    private array $config;
    private AMQPStreamConnection $connection;
    private array $channels;

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

    public function openChannel(string $tag): void
    {
        if (!isset($this->channel[$tag])) {
            $this->channels[$tag] = $this->connection->channel();
        }
    }

    public function getChannel(string $tag): AMQPChannel
    {
        return $this->channels[$tag];
    }

    public function closeChannel(string $tag): void
    {
        $this->channels[$tag]->close();
    }

    public function reopenChannel(string $tag): void
    {
        if (!$this->channels[$tag]->is_open()) {
            $this->channels[$tag] = $this->connection->channel();
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }


    public function closeConnection(): void
    {
        $this->connection->close();
    }
}