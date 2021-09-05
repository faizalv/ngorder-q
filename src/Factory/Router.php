<?php

namespace Ngorder\Q\Factory;

class Router
{
    private $routing;

    public function __construct(array $routing)
    {
        $this->routing = $routing;
    }

    public function checkRoutingKey(string $routing_key): bool
    {
        return array_key_exists($routing_key, $this->routing);
    }

    public function getRoutingKeys(): array
    {
        return array_keys($this->routing);
    }

    public function getConsumer(string $routing_key)
    {
        if (isset($this->routing[$routing_key])) {
            $consumer = $this->routing[$routing_key];
            if ((is_array($consumer) && is_callable($consumer)) || is_callable(new $consumer())) {
                return $consumer;
            }
        }
        return null;
    }
}