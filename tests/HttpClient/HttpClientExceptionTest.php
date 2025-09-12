<?php

declare(strict_types=1);

namespace Tests\HttpClient;

use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class HttpClientExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new HttpClientException('Test message');
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
