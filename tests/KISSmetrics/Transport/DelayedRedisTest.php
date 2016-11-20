<?php

namespace KISSmetrics\Transport;

class DelayedRedisTest extends \PHPUnit_Framework_TestCase {

  public function testDefaults() {
    $km_api = DelayedRedis::initDefault();
    $this->assertEquals('trk.kissmetrics.com', $km_api->getHost());
    $this->assertEquals(80, $km_api->getPort());
    $this->assertEquals('KISSmetrics', $km_api->getRedisPrefix());
    $this->assertEquals('127.0.0.1', $km_api->getRedisHost());
    $this->assertEquals(6379, $km_api->getRedisPort());
    $this->assertEquals(0, $km_api->getRedisDatabase());
  }

  public function testSubmitData() {
    $km_api = DelayedRedis::initDefault('localhost', 6379);

    $queryData1 = array(
      'a',
      array(
        '_p' => 'doctor@gallifrey',
        '_n' => 'john@smith',
        '_k' => '12345',
        '_t' => 1479507634,
        '_d' => TRUE
      )
    );

    $queryData2 = array(
      'a',
      array(
        '_p' => 'doctor@who',
        '_n' => 'john@doe',
        '_k' => '54321',
        '_t' => 1479507634,
        '_d' => TRUE
      )
    );

    $redis = $this->getMock('Redis', array(
      'rPush'
    ), array(), '', FALSE);

    $redis->expects($this->at(0))
      ->method('rPush', 'rPush')
      ->with('KISSmetrics_events', serialize($queryData1));

    $redis->expects($this->at(1))
      ->method('rPush')
      ->with('KISSmetrics_events', serialize($queryData2));

    $km_api->setRedisInstance($redis);

    $km_api->submitData(array(
        $queryData1,
        $queryData2,
    ));

  }

  public function testSendLoggedData() {
    $km_api = DelayedRedis::initDefault('localhost', 6379);

    $queryData1 = array(
      'a',
      array(
        '_p' => 'doctor@gallifrey',
        '_n' => 'john@smith',
        '_k' => '12345',
        '_t' => 1479507634,
        '_d' => TRUE
      )
    );

    $queryData2 = array(
      'a',
      array(
        '_p' => 'doctor@who',
        '_n' => 'john@doe',
        '_k' => '54321',
        '_t' => 1479507634,
        '_d' => TRUE
      )
    );

    $redis = $this->getMock('Redis', array(
      'lRange', 'delete'
    ), array(), '', FALSE);

    $redis->expects($this->exactly(1))
      ->method('lRange')
      ->with('KISSmetrics_events', 0, -1)
      ->will($this->returnValue(
          array(
            serialize($queryData1),
            serialize($queryData2)
          )
      ));

    $redis->expects($this->exactly(1))
      ->method('delete')
      ->with('KISSmetrics_events');

    $km_api->setRedisInstance($redis);

    $km_api->sendLoggedData();

  }


}