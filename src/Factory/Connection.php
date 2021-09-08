<?php

namespace Ngorder\Q\Factory;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

class Connection
{
    private $connection, $channel, $config, $routing_key, $queue_name, $exchange_name, $exchange_type;
    public $code;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(): Connection
    {
        $connection_config = $this->config['connections'][$this->config['connect_to']];
        $this->connection = new AMQPStreamConnection(
            $connection_config['host'] ?? 'localhost',
            $connection_config['port'] ?? 5672,
            $connection_config['user'] ?? 'guest',
            $connection_config['pass'] ?? 'guest',
            $connection_config['vhost'] ?? '/'
        );
        $this->channel = $this->connection->channel();
        return $this;
    }

    public function setRoute(string $routing_key): Connection
    {
        $this->routing_key = $routing_key;
        return $this;
    }

    public function makeExchange(?string $name = null, ?string $type = null): Connection
    {
        if (empty($name) || empty($type)) {
            $this->exchange_name = $this->config['exchange']['name'];
            $this->exchange_type = $this->config['exchange']['type'];
        } else {
            $this->exchange_name = $name;
            $this->exchange_type = $type;
        }
        $this->channel->exchange_declare($this->exchange_name, $this->exchange_type);
        return $this;
    }

    public function makeQueue(?string $name = null, ?array $arguments = []): Connection
    {
        $this->queue_name = $this->createQName($name);
        $arguments = $this->parseQArguments($arguments);
        $this->channel->queue_declare($this->queue_name,
            false,
            true,
            false,
            false,
            false, new AMQPTable($arguments));
        return $this;
    }

    public function bind(): Connection
    {
        $this->channel->queue_bind($this->queue_name, $this->exchange_name, $this->routing_key);
        return $this;
    }

    public function getConnection(): AMQPStreamConnection
    {
        return $this->connection;
    }

    public function getChannel(): \PhpAmqpLib\Channel\AMQPChannel
    {
        return $this->channel;
    }

    public function getExchangeName()
    {
        return $this->exchange_name;
    }

    public function getQName()
    {
        return $this->queue_name;
    }

    public function getActiveConfig()
    {
        return $this->config;
    }

    public function reopenChannel()
    {
        if (!$this->channel->is_open()) {
            $this->channel = $this->connection->channel();
        }
    }

    private function createQName($name): string
    {
        $prefix = $this->config['queue']['naming']['prefix'];
        if (empty($prefix)) {
            $prefix = 'NgorderQ';
        }
        if (is_null($name)) {
            if (is_null($this->routing_key)) {
                $time = (new \DateTime())->format('YmdHisu');
                return $prefix . ':' . $time;
            }
            return $prefix . ':' . $this->routing_key;
        }
        return $prefix . ':' . $name;
    }

    private function parseQArguments($arguments)
    {
        if (empty($arguments)) {
            if (isset($this->config['queue']['arguments']['type'])) {
                $arguments['x-queue-type'] = $this->config['queue']['arguments']['type'];
            }
        }
        return $arguments;
    }
}