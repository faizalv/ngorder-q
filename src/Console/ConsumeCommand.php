<?php

namespace Ngorder\Q\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Ngorder\Q\Consumer;

class ConsumeCommand extends Command
{
    protected $signature = 'q:consume 
                            {routing_key : The routing key to listen for} 
                            {--tries=3 : Number of times to try re-consume a message before discards it}
                            {--max-memory=128 : Max memory}';
    protected $description = 'Consume incoming message';

    public function handle(Consumer $consumer)
    {
        $consumer->connect();
        $consumer->prepare($this->argument('routing_key'));
        $consumer->setMaxTries($this->option('tries'));
        $consumer->setMaxMemory($this->option('max-memory'));
        $consumer->work();
    }
}