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
     * @param string $tag
     * @return void
     */
    public function openChannel(string $tag): void;

    /**
     * @param string $tag
     * @return AMQPChannel|mixed
     */
    public function getChannel(string $tag): object;

    /**
     * @param string $tag
     * @return void
     */
    public function closeChannel(string $tag): void;

    /**
     * @param string $tag
     * @return void
     */
    public function reopenChannel(string $tag): void;

    /**
     * @return array
     */
    public function getConfig(): array;
}