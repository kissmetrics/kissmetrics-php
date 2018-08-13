<?php

namespace KISSmetrics\Transport;

use PHPUnit\Framework\TestCase;

class SocketsTest extends TestCase
{
    public function testDefaults()
    {
        $km_api = Sockets::initDefault();
        $this->assertEquals('trk.kissmetrics.com', $km_api->getHost());
        $this->assertEquals(80, $km_api->getPort());
    }
}
