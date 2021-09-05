<?php

namespace Ngorder\Q\Console;

use Illuminate\Console\Command;
use Ngorder\Q\Factory\Consumer;

class QConsume extends Command
{
    protected $signature = 'q:consume {routing_key}';
    protected $description = 'Consume incoming message';

    public function handle(Consumer $consumer)
    {
        $this->info("Starting consume");
        try {
            $consumer->handle($this->laravel['routing_key']);
            $consumer->work();
        } catch (\Exception $e) {
            $this->error($e);
        }
    }
}