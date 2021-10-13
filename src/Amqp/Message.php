<?php

namespace Ngorder\Q\Amqp;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Message
{
    private array $headers;
    private AMQPMessage $parent;

    /**
     * @param string|array $message
     * @param AMQPTable|array|null $headers
     */
    public function __construct($message = null, $headers = null)
    {
        if (is_null($message)) return;
        if (is_null($headers)) {
            if (is_array($message)) {
                $this->headers['Content-type'] = 'application/json';
                $message = json_encode($message);
            } else {
                $this->headers['Content-type'] = 'text/plain';
            }
        } else {
            $this->headers = $headers;
        }

        $this->parent = new AMQPMessage($message, [
            'application_headers' => new AMQPTable($this->headers)
        ]);
    }

    public function setParent(AMQPMessage $message): Message
    {
        $this->parent = $message;
        return $this;
    }

    public function getMessage()
    {
        $content_type = $this->getHeaders()['Content-type'] ?? null;
        return $content_type === 'application/json' ? json_decode($this->parent->body, true) : $this->parent->body;
    }

    public function getRawMessage(): string
    {
        return $this->parent->body;
    }

    public function getHeaders()
    {
        return $this->parent->get_properties()['application_headers']->getNativeData();
    }

    public function ack()
    {
        $this->parent->ack();
    }

    public function nack()
    {
        $this->parent->nack();
    }

    public function parent(): AMQPMessage
    {
        return $this->parent;
    }
}