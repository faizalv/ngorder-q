<?php

namespace Ngorder\Q\Testing;

class Assert extends \PHPUnit\Framework\Assert
{
    public static function assertTest()
    {
        static::assertTrue(false, 'Its TRUE!');
    }
}