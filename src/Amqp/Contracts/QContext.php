<?php

namespace Ngorder\Q\Amqp\Contracts;

interface QContext
{
    const E_PASSIVE = false;
    const E_DURABLE = true;
    const E_AUTO_DELETE = true;
    const E_INTERNAL = false;
    const E_NOWAIT = false;
    const Q_PASSIVE = false;
    const Q_DURABLE = true;
    const Q_EXCLUSIVE = false;
    const Q_AUTO_DELETE = false;
    const Q_NOWAIT = false;

    /**
     * @param string $routing_key
     * @return QContext
     */
    public function setRoutingKey(string $routing_key): QContext;

    /**
     * @param string|null $queue_name
     * @param array|null $queue_args
     * @return QContext
     */
    public function makeQueue(?string $queue_name, ?array $queue_args): QContext;

    /**
     * @param string|null $exchange_name
     * @param string|null $exchange_type
     * @return QContext
     */
    public function makeExchange(?string $exchange_name, ?string $exchange_type): QContext;

    /**
     * @return string
     */
    public function getExchangeName(): string;

    /**
     * @return string
     */
    public function getQueueName(): string;

    /**
     * @return string
     */
    public function getRoutingKey(): string;

    /**
     * @return void
     */
    public function bind(): void;
}