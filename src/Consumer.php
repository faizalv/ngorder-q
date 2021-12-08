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
use Ngorder\Q\Amqp\Router;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer
{
    private QConnection $connection;
    private QContext $context;
    private array $consumers;
    private array $failed_consumers;
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
            $this->consumers = $this->router()->getConsumer($this->routing_key);
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
        $this->router()->receive(
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
        $success = $this->fire($message);
        if ($success) {
            $this->ack($message);
        } else {
            $success_retry = $this->retry($message);
            if ($success_retry) {
                $this->ack($message);
            } else {
                $this->nack($message);
            }
            $this->failed_consumers = [];
            $this->tries = 0;
        }
    }

    private function fire(Message $message): bool
    {
        $routing_key = $message->parent()->getRoutingKey();
        if ($this->consumers['type'] === $this->router()::SOME_CALLABLE) {
            $fail = 0;
            foreach ($this->consumers['func'] as $consumer) {
                try {
                    call_user_func($consumer, $message->getMessage());
                    $this->sendHandledLog(
                        get_class($consumer[0]),
                        $consumer[1],
                        $routing_key
                    );
                } catch (\Throwable $t) {
                    $reason = $this->getTime() . $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine();
                    $this->failed_consumers[$fail]['reason'] = $reason;
                    $this->failed_consumers[$fail]['func'] = $consumer;
                    $fail++;
                    Log::error($reason);
                    Help::print(
                        $reason,
                        'error'
                    );
                }
            }
        } else {
            try {
                call_user_func($this->consumers['func'], $message->getMessage());
                $this->sendHandledLog(
                    get_class($this->consumers['func'][0]),
                    $this->consumers['func'][1],
                    $routing_key
                );
            } catch (\Throwable $t) {
                $this->failed_consumers[0]['reason'] = $this->getTime() . $t->getMessage() . ' in ' . $t->getFile(
                    ) . ':' . $t->getLine();
                $this->failed_consumers[0]['func'] = $this->consumers['func'];
            }
        }
        return empty($this->failed_consumers);
    }

    private function retry(Message $message)
    {
        $routing_key = $message->parent()->getRoutingKey();
        while ($this->tries < $this->max_tries) {
            foreach ($this->failed_consumers as $index => $failed_consumer) {
                if ($this->tries === 0) {
                    Help::print(
                        $failed_consumer['reason'],
                        'error'
                    );
                }
                $class_string = get_class($failed_consumer['func'][0]);
                Help::print($this->getTime() . 'Retrying... ' . $class_string . '@' . $failed_consumer['func'][1]);
                try {
                    call_user_func($failed_consumer['func'], $message->getMessage());
                    $this->sendHandledLog(
                        $class_string,
                        $failed_consumer['func'][1],
                        $routing_key
                    );
                    unset($this->failed_consumers[$index]);
                } catch (\Throwable $t) {
                    Help::print(
                        $this->getTime() . $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine(),
                        'error'
                    );
                }
            }
            $this->tries++;
        }

        return empty($this->failed_consumers);
    }

    private function ack(Message $message): void
    {
        $message->ack();
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

    private function sendHandledLog(string $class, string $method, string $routing_key): void
    {
        $log = 'Message from ' . $routing_key . ' successfully handled by '
            . $class . '@' . $method;
        Log::info($log);
        Help::print($this->getTime() . $log);
    }

    private function checkMemory(): void
    {
        if (round(memory_get_usage() / 1024 / 1024, PHP_ROUND_HALF_DOWN) > $this->max_memory) {
            $this->stop();
        }
    }

    private function stop(): void
    {
        Help::print($this->getTime() . 'Maximum memory usage exceeded, stopping...');
        exit;
    }

    private function getTime(): string
    {
        return '[' . (new \DateTime())->format('Y-m-d H:i:s') . '] ';
    }

    private function router(): Router
    {
        return Instances::getRouter();
    }
}