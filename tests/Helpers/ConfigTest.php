<?php

declare(strict_types=1);

namespace Helpers;

use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\Helpers\Config;

class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock environment variables
        $_ENV = [
            'httpclient' => 'Guzzle',
            'guzzle.timeout' => '30',
            'guzzle.retry.enabled' => 'true',
            'guzzle.retry.retry_on_status' => '500,502,504',
        ];
        $this->config = new Config();
    }

    public function testGetString(): void
    {
        $this->assertSame('Guzzle', $this->config->get('httpclient'));
    }

    public function testGetInt(): void
    {
        $this->assertSame(30, $this->config->get('guzzle.timeout'));
    }

    public function testGetBool(): void
    {
        $this->assertTrue($this->config->get('guzzle.retry.enabled'));
    }

    public function testGetArray(): void
    {
        $this->assertSame(['500', '502', '504'], $this->config->get('guzzle.retry.retry_on_status'));
    }

    public function testGetNull(): void
    {
        $this->assertNull($this->config->get('nonexistent_key'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertSame('default_value', $this->config->get('nonexistent_key', 'default_value'));
    }
}
