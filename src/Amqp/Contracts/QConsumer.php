<?php

namespace Ngorder\Q\Amqp\Contracts;

interface QConsumer
{
    public const DEFAULT_CONSUMER_TAG = '';
    public const Q_NO_LOCAL = false;
    public const Q_NO_ACK = false;
    public const Q_EXCLUSIVE = false;
    public const Q_NOWAIT = false;
}