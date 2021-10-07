<?php

namespace Ngorder\Q\Amqp\Contracts;

interface QConsumer
{
    const DEFAULT_CONSUMER_TAG = '';
    const Q_NO_LOCAL = false;
    const Q_NO_ACK = false;
    const Q_EXCLUSIVE = false;
    const Q_NOWAIT = false;
}