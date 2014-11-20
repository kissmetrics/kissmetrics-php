<?php

namespace KISSmetrics;

class ClientTest extends \PHPUnit_Framework_TestCase {
  protected $client;

  public function setUp() {
    $this->client = new Client('12345', new Transport\Mock());
  }

  public function testInitialization() {
    $this->assertInstanceOf('KISSmetrics\\Client', $this->client);
    $this->assertEquals('12345', $this->client->getKey());
  }

  /**
   * @expectedException KISSmetrics\ClientException
   */
  public function testExceptions() {
    $this->client->record('Slayed a dragon');
  }

  public function testIdentify() {
    $this->client->identify('john@smith');
    $this->assertEquals('john@smith', $this->client->getId());
  }

  public function testRecord() {
    $this->client->identify('john@smith');
    $this->client->record('Purchased thing', array(), 0);

    $this->assertEquals(array(
      array(
        'e',
        array(
          '_n'   => 'Purchased thing',
          '_p'   => 'john@smith',
          '_k'   => '12345',
          '_t'   => 0,
          '_d'   => true
        ),
      ),
    ), $this->client->getQueries());
  }

  public function testRecordWithTimeAsNull() {
    $this->client->identify('john@smith');
    $this->client->record('Purchased thing', array());

    $this->assertEquals(array(
      array(
        'e',
        array(
          '_n'   => 'Purchased thing',
          '_p'   => 'john@smith',
          '_k'   => '12345',
          '_t'   => time(),
          '_d'   => false
        ),
      ),
    ), $this->client->getQueries());
  }

  public function testSetWithTimeAsNull() {
    $this->client->identify('john@smith');
    $this->client->set(array('eyes' => 'blue'));

    $this->assertEquals(array(
      array(
        's',
        array(
          'eyes' => 'blue',
          '_p'   => 'john@smith',
          '_k'   => '12345',
          '_t'   => time(),
          '_d'   => null
        ),
      ),
    ), $this->client->getQueries());
  }

  public function testSet() {
    $this->client->identify('john@smith');
    $this->client->set(array('eyes' => 'blue'), 0);

    $this->assertEquals(array(
      array(
        's',
        array(
          'eyes' => 'blue',
          '_p'   => 'john@smith',
          '_k'   => '12345',
          '_t'   => 0,
          '_d'   => true
        ),
      ),
    ), $this->client->getQueries());
  }

  public function testAlias() {
    $this->client->identify('john@smith');
    $this->client->alias('doctor@gallifrey');

    $this->assertEquals(array(
      array(
        'a',
        array(
          '_p' => 'doctor@gallifrey',
          '_n' => 'john@smith',
          '_k' => '12345',
        ),
      ),
    ), $this->client->getQueries());
  }
}
