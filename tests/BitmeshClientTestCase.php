<?php

namespace BitmeshAI\Tests;

use PHPUnit\Framework\TestCase;

abstract class BitmeshClientTestCase extends TestCase
{
    protected function getConsumerKey(): string
    {
        $key = getenv('BITMESH_TEST_CONSUMER_KEY');
        return $key !== false ? $key : $_ENV['BITMESH_TEST_CONSUMER_KEY'] ?? '';
    }

    protected function getConsumerSecret(): string
    {
        $secret = getenv('BITMESH_TEST_CONSUMER_SECRET');
        return $secret !== false ? $secret : $_ENV['BITMESH_TEST_CONSUMER_SECRET'] ?? '';
    }
}
