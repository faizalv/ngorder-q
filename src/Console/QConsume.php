<?php

namespace Ngorder\Q\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Ngorder\Q\Factory\Consumer;

class QConsume extends Command
{
    protected $signature = 'q:consume {routing_key}';
    protected $description = 'Consume incoming message';

    public function handle(Consumer $consumer)
    {
        try {
            $consumer->prepare($this->argument('routing_key'));
            $consumer->connect();
            $this->info("Waiting for message...");
            $consumer->work();
        } catch (\ErrorException $e) {
            Log::error($e);
            $this->error($e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        } catch (\Exception $e) {
            Log::error($e);
            $this->error($e->getMessage());
        }
    }
}