<?php

namespace Ngorder\Q\Amqp\Contracts;

interface QueueType
{
    public const CLASSIC = 'classic';
    public const QUORUM = 'quorum';
}