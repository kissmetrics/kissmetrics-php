<?php

namespace KISSmetrics\Transport;

use PHPUnit\Framework\TestCase;

class DelayedTransportTest extends TestCase {
  /**
   * @var KISSmetrics\Transport\Transport
   */
  protected $km_api;

  public function setUp()
  {
    $this->km_api = Delayed::initDefault('/tmp');
    $this->km_api->setLogDir('/tmp');
  }

  public function testGetLogDir() {
    $this->assertEquals($this->km_api->getLogDir(), '/tmp');
  }

  public function testItCreatesANewKissmetricsQueryFileWhenItDoesNotExists()
  {
    @unlink('/tmp/kissmetrics_query.log');

    $this->km_api->sendLoggedData();

    $this->assertFileExists('/tmp/kissmetrics_query.log');
  }
}
