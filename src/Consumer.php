<?php

namespace Ngorder\Q;

use Closure;
use Illuminate\Support\Facades\Log;
use Ngorder\Q\Amqp\Context;
use Ngorder\Q\Amqp\Contracts\QConnection;
use Ngorder\Q\Amqp\Contracts\QContext;
use Ngorder\Q\Amqp\Helpers\Help;
use Ngorder\Q\Amqp\Helpers\Instances;
use Ngorder\Q\Amqp\Message;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer
{
    private QConnection $connection;
    private QContext $context;
    private array $consumer;
    private array $consumer_string;
    private ?string $exchange_name = null;
    private ?string $exchange_type = null;
    private ?string $queue_name = null;
    private ?array $queue_args = null;
    private string $routing_key;
    private int $max_tries = 3;
    private int $tries = 0;
    private int $max_memory = 128;
    public Closure $callback;

    public function __construct(QConnection $connection)
    {
        $this->connection = $connection;
    }

    public function connect()
    {
        $this->connection->connect();
        $this->connection->openChannel('consume');
        $this->connection->getChannel('consume')->basic_qos(null, 1, false);
    }

    public function prepare(string $routing_key)
    {
        $this->routing_key = $routing_key;
        try {
            $this->consumer = Instances::getRouter()->getConsumer($this->routing_key);
            $this->consumer_string = [get_class($this->consumer[0]), $this->consumer[1]];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Help::print($this->getTime() . $e->getMessage(), 'error');
            exit;
        }
        $this->createContext();
        $this->prepareCallback();
    }

    public function setMaxTries(int $max_tries): void
    {
        $this->max_tries = abs($max_tries);
    }

    public function setMaxMemory(int $max_memory): void
    {
        $this->max_memory = $max_memory;
    }

    public function work()
    {
        Instances::getRouter()->receive(
            $this->connection,
            $this->context,
            $this->callback
        );

        Help::print($this->getTime() . 'Started');

        while (true) {
            $this->connection->getChannel('consume')->wait();
        }
    }

    private function createContext(): void
    {
        $this->context = new Context($this->connection, 'consume');
        $this->context->setRoutingKey($this->routing_key)
            ->makeExchange($this->exchange_name, $this->exchange_type)
            ->makeQueue($this->queue_name, $this->queue_args)
            ->bind();
    }

    private function prepareCallback()
    {
        $this->callback = function (AMQPMessage $message) {
            $message = (new Message())->setParent($message);
            $this->consume($message);
        };
    }

    private function consume(Message $message): void
    {
        try {
            call_user_func($this->consumer, $message->getMessage());
            $this->ack($message);
        } catch (\Exception $e) {
            if ($this->tries === $this->max_tries) {
                $this->nack($message);
                $this->tries = 0;
            } else {
                Help::print(
                    $this->getTime() . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                    'error'
                );
                Log::error($e->getMessage());
                Help::print($this->getTime() . 'Retrying...');
                $this->tries++;
                $this->consume($message);
            }
        }
    }

    private function ack(Message $message): void
    {
        $message->ack();
        $log = 'Message from ' . $message->parent()->getRoutingKey() . ' successfully handled by '
            . $this->consumer_string[0] . '::' . $this->consumer_string[1];
        Log::info($log);
        Help::print($this->getTime() . $log);
        $this->checkMemory();
    }

    private function nack(Message $message): void
    {
        $log = 'Cannot process the message, dropping it...';
        Help::print($this->getTime() . $log, 'error');
        Log::error($log);
        Log::error('{"message":"' . $message->getRawMessage() . '"}');
        $message->nack();
    }

    private function checkMemory()
    {
        if (round(memory_get_usage() / 1024 / 1024, PHP_ROUND_HALF_DOWN) > $this->max_memory) {
            $this->stop();
        }
    }

    private function stop()
    {
        Help::print($this->getTime() . 'Maximum memory usage exceeded, stopping...');
        exit;
    }

    private function getTime(): string
    {
        return '[' . (new \DateTime())->format('Y-m-d H:i:s') . '] ';
    }
}