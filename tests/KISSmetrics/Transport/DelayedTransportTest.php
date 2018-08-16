<?php

namespace KISSmetrics\Transport;

use PHPUnit\Framework\TestCase;

class DelayedTransportTest extends TestCase
{
    /**
     * @var KISSMetrics\Client
     */
    protected $kmApi;

    /**
     * @var KISSmetrics\Transport\Delayed
     */
    protected $kmTransport;

    public function setUp()
    {
        $this->kmTransport = Delayed::initDefault('/tmp');
        $this->kmTransport->setLogDir('/tmp');
    }

    public function testGetLogDir()
    {
        $this->assertEquals($this->kmTransport->getLogDir(), '/tmp');
    }

    public function testItCreatesANewKissmetricsQueryFileWhenItDoesNotExists()
    {
        @unlink('/tmp/kissmetrics_query.log');

        $this->kmTransport->sendLoggedData();

        $this->assertFileExists('/tmp/kissmetrics_query.log');
    }

    public function testItDoesnotSubmitsEmptyQueries()
    {
        $this->expectException(NoQueriesException::class);

        $this->kmTransport->submitData([]);
    }

    public function testItDoesSubmitQueriesWithData()
    {
        @unlink('/tmp/kissmetrics_query.log');

        $this->kmApi = new \KISSmetrics\Client(
            'test',
            $this->kmTransport
        );

        $this->kmApi
            ->identify('example@example.com')
            ->record('Runned test');

        $this->kmApi->submit();

        $this->assertFileExists('/tmp/kissmetrics_query.log');
        $kmQueryLog = file_get_contents('/tmp/kissmetrics_query.log');
        $this->assertContains('example@example.com', $kmQueryLog);
        $this->assertContains('Runned test', $kmQueryLog);
    }
}
