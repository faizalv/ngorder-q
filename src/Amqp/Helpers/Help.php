<?php

namespace Ngorder\Q\Amqp\Helpers;

use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\Console\Output\ConsoleOutput;

class Help
{
    public static function createQName(array $q_config, ?string $name, ?string $routing_key): string
    {
        $prefix = $q_config['queue']['naming']['prefix'];
        if (empty($prefix)) {
            $prefix = 'NgorderQ';
        }
        if (is_null($name)) {
            if (is_null($routing_key)) {
                $time = (new \DateTime())->format('YmdHisu');
                return $prefix . ':' . $time;
            }
            return $prefix . ':' . $routing_key;
        }
        return $prefix . ':' . $name;
    }

    public static function parseQArguments(array $config, ?array $arguments): AMQPTable
    {
        if (empty($arguments)) {
            if (isset($config['queue']['arguments']['type'])) {
                $arguments['x-queue-type'] = $config['queue']['arguments']['type'];
            }
        }
        return new AMQPTable($arguments);
    }

    public static function print(string $message, string $type = 'info')
    {
        $writer = new ConsoleOutput();
        switch ($type) {
            case 'error':
                $t1 = '<error>';
                $t2 = '</error>';
                break;
            case 'warn':
                $t1 = '<warn>';
                $t2 = '</warn>';
                break;
            default:
                $t1 = '<info>';
                $t2 = '</info>';
        }
        $writer->writeln($t1 . $message . $t2);
    }

    public static function getMemoryUsage()
    {
        
    }
}