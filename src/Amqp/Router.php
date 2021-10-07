<?php

namespace Ngorder\Q\Amqp;

use Closure;
use Exception;
use Ngorder\Q\Amqp\Contracts\QConnection;
use Ngorder\Q\Amqp\Contracts\QConsumer;
use Ngorder\Q\Amqp\Contracts\QContext;

class Router
{
    private array $routing;

    public function __construct(array $routing)
    {
        $this->routing = $routing;
    }

    public function hasRegistered(string $routing_key): bool
    {
        return array_key_exists($routing_key, $this->routing);
    }

    public function getRoutingKeys(): array
    {
        return array_keys($this->routing);
    }

    /**
     * @param string $routing_key
     * @return array|null
     * @throws Exception
     */
    public function getConsumer(string $routing_key): ?array
    {
        if ($this->hasRegistered($routing_key)) {
                $consumer = $this->routing[$routing_key];
                if (is_array($consumer) && is_callable($consumer)) {
                    return [new $consumer[0](), $consumer[1]];
                } elseif (is_callable(new $consumer())) {
                    return [new $consumer(), '__invoke'];
                }
            return null;
        }
        throw new Exception('Unknown routing key: [' . $routing_key . '] Be sure to register that routing key in QServiceProvider before using it');
    }

    public function send(Message $message, QConnection $connection, QContext $context): void
    {
        $connection->getChannel()->basic_publish(
            $message->parent(),
            $context->getExchangeName(),
            $context->getRoutingKey()
        );
        $connection->getChannel()->close();
    }

    /**
     * @throws Exception
     */
    public function receive(QConnection $connection, QContext $context, Closure $callback): void
    {
        $routing_key = $context->getRoutingKey();
        if ($this->hasRegistered($routing_key)) {
            $connection->getChannel()->basic_consume(
                $context->getQueueName(),
                QConsumer::DEFAULT_CONSUMER_TAG,
                QConsumer::Q_NO_LOCAL,
                QConsumer::Q_NO_ACK,
                QConsumer::Q_EXCLUSIVE,
                QConsumer::Q_NOWAIT,
                $callback,
            );
        } else {
            throw new Exception('Unknown routing key: [' . $routing_key . '] Be sure to register that routing key in QServiceProvider before using it');
        }
    }
}