<?php

namespace KISSmetrics\Transport;

use PHPUnit\Framework\TestCase;

class SocketsTest extends TestCase
{
    public function testDefaults()
    {
        $kmApi = Sockets::initDefault();
        $this->assertEquals('trk.kissmetrics.com', $kmApi->getHost());
        $this->assertEquals(80, $kmApi->getPort());
    }
}
