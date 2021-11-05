<?php

namespace Ngorder\Q\Amqp\Contracts;

interface ExchangeType
{
    public const FANOUT = 'fanout';
    public const TOPIC = 'topic';
}