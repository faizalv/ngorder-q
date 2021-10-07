<?php

namespace Ngorder\Q\Amqp\Contracts;


use PhpAmqpLib\Channel\AMQPChannel;

interface QConnection
{
    /**
     * @return QConnection
     */
    public function connect(): QConnection;

    /**
     * @return void
     */
    public function openChannel(): void;

    /**
     * @return AMQPChannel|mixed
     */
    public function getChannel(): object;

    /**
     * @return void
     */
    public function closeChannel(): void;

    /**
     * @return void
     */
    public function reopenChannel(): void;

    /**
     * @return array
     */
    public function getConfig(): array;
}