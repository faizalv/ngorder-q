<?php

namespace Ngorder\Q\Amqp;

use Closure;
use Exception;
use Ngorder\Q\Amqp\Contracts\QConnection;
use Ngorder\Q\Amqp\Contracts\QConsumer;
use Ngorder\Q\Amqp\Contracts\QContext;

class Router
{
    public const ARRAY_CALLABLE = 'array_callable';
    public const STRING_CALLABLE = 'string_callable';
    public const SOME_CALLABLE = 'some_callable';
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
     * @return array
     * @throws Exception
     */
    public function getConsumer(string $routing_key): ?array
    {
        if (!$this->hasRegistered($routing_key)) {
            throw new Exception(
                'Unknown routing key: [' . $routing_key . '] Be sure to register that routing key in QServiceProvider before using it'
            );
        }
        $consumers = $this->routing[$routing_key];
        if (is_array($consumers)) {
            if (isset($consumers[0])) {
                if (is_string($consumers[0]) && class_exists($consumers[0]) && isset($consumers[1])
                    && is_string($consumers[1]) && is_callable([new $consumers[0](), $consumers[1]])) {
                    return [
                        'type' => self::ARRAY_CALLABLE,
                        'func' => [
                            new $consumers[0](),
                            $consumers[1]
                        ]
                    ];
                }

                $list = [];
                foreach ($consumers as $consumer) {
                    if (is_string($consumer) && class_exists($consumer) && is_callable(new $consumer())) {
                        $list[] = [
                            new $consumer(),
                            '__invoke'
                        ];
                    } elseif (is_array($consumer) && class_exists($consumer[0]) && is_callable(
                            [new $consumer[0](), $consumer[1]]
                        )) {
                        $list[] = [
                            new $consumer[0](),
                            $consumer[1]
                        ];
                    }
                }
                if (!empty($list)) {
                    return [
                        'type' => self::SOME_CALLABLE,
                        'count' => count($list),
                        'func' => $list
                    ];
                }
            }
        } elseif (is_string($consumers) && class_exists($consumers) && is_callable(new $consumers())) {
            return [
                'type' => self::STRING_CALLABLE,
                'func' => [
                    new $consumers(),
                    '__invoke'
                ]
            ];
        }
        throw new Exception('No callable is given');
    }

    public function send(Message $message, QConnection $connection, QContext $context): void
    {
        $connection->getChannel('publish')->basic_publish(
            $message->parent(),
            $context->getExchangeName(),
            $context->getRoutingKey()
        );
    }

    /**
     * @throws Exception
     */
    public function receive(QConnection $connection, QContext $context, Closure $callback): void
    {
        $routing_key = $context->getRoutingKey();
        if ($this->hasRegistered($routing_key)) {
            $connection->getChannel('consume')->basic_consume(
                $context->getQueueName(),
                QConsumer::DEFAULT_CONSUMER_TAG,
                QConsumer::Q_NO_LOCAL,
                QConsumer::Q_NO_ACK,
                QConsumer::Q_EXCLUSIVE,
                QConsumer::Q_NOWAIT,
                $callback,
            );
        } else {
            throw new Exception(
                'Unknown routing key: [' . $routing_key . '] Be sure to register that routing key in QServiceProvider before using it'
            );
        }
    }
}