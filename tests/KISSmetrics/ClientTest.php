<?php

namespace KISSmetrics;

use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected $client;

    public function setUp()
    {
        $this->client = new Client('12345', new Transport\Mock());

        parent::setUp();
    }

    public function testInitialization()
    {
        $this->assertInstanceOf('KISSmetrics\\Client', $this->client);
        $this->assertEquals('12345', $this->client->getKey());
    }

    /**
     * @expectedException KISSmetrics\ClientException
     */
    public function testExceptions()
    {
        $this->client->record('Slayed a dragon');
    }

    public function testIdentify()
    {
        $this->client->identify('john@smith');
        $this->assertEquals('john@smith', $this->client->getId());
    }

    public function testRecord()
    {
        $this->client->identify('john@smith');
        $this->client->record('Purchased thing', [], 0);

        $this->assertEquals([
            [
                'e',
                [
                    '_n' => 'Purchased thing',
                    '_p' => 'john@smith',
                    '_k' => '12345',
                    '_t' => 0,
                    '_d' => true,
                ],
            ],
        ], $this->client->getQueries());
    }

    public function testRecordWithTimeAsNull()
    {
        $this->client->identify('john@smith');
        $this->client->record('Purchased thing', []);

        $this->assertEquals([
            [
                'e',
                [
                    '_n' => 'Purchased thing',
                    '_p' => 'john@smith',
                    '_k' => '12345',
                    '_t' => time(),
                    '_d' => false,
                ],
            ],
        ], $this->client->getQueries());
    }

    public function testSetWithTimeAsNull()
    {
        $this->client->identify('john@smith');
        $this->client->set(['eyes' => 'blue']);

        $this->assertEquals([
            [
                's',
                [
                    'eyes' => 'blue',
                    '_p'   => 'john@smith',
                    '_k'   => '12345',
                    '_t'   => time(),
                    '_d'   => null,
                ],
            ],
        ], $this->client->getQueries());
    }

    public function testSet()
    {
        $this->client->identify('john@smith');
        $this->client->set(['eyes' => 'blue'], 0);

        $this->assertEquals([
            [
                's',
                [
                    'eyes' => 'blue',
                    '_p'   => 'john@smith',
                    '_k'   => '12345',
                    '_t'   => 0,
                    '_d'   => true,
                ],
            ],
        ], $this->client->getQueries());
    }

    public function testAlias()
    {
        $this->client->identify('john@smith');
        $this->client->alias('doctor@gallifrey');

        $this->assertEquals([
            [
                'a',
                [
                    '_p' => 'doctor@gallifrey',
                    '_n' => 'john@smith',
                    '_k' => '12345',
                ],
            ],
        ], $this->client->getQueries());
    }
}
