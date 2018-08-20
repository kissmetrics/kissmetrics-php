<?php

namespace KISSmetrics\Transport;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class DelayedTransportTest extends TestCase
{
    /**
     * @var \KISSMetrics\Client
     */
    protected $kmApi;

    /**
     * @var \KISSmetrics\Transport\Delayed
     */
    protected $kmTransport;

    public function setUp()
    {
        $this->kmTransport = new Delayed();
        $this->kmTransport->setLogDir('/tmp');

        parent::setUp();
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

    public function testItDoesnotStoreEmptyQueries()
    {
        $this->expectException(NoQueriesException::class);

        $this->kmTransport->submitData([]);
    }

    public function testItDoesStoreQueriesWithData()
    {
        $this->createAndFillQueryLogFile();

        $this->assertFileExists('/tmp/kissmetrics_query.log');
        $kmQueryLog = file_get_contents('/tmp/kissmetrics_query.log');
        $this->assertContains('example@example.com', $kmQueryLog);
        $this->assertContains('Runned test', $kmQueryLog);

        $this->assertContains('example2@example.com', $kmQueryLog);
        $this->assertContains('Runned test 2', $kmQueryLog);
    }

    public function testItDoesSendQueriesToTheKissmetricsApi()
    {
        $guzzleClient = m::mock(\GuzzleHttp\Client::class.'[request]');
        $guzzleClient->shouldReceive('request')
            ->once()
            ->withArgs([
                'GET', 'https://trk.kissmetrics.com/e?_n=Runned%20test&_p=example%40example.com&_k=test&_t='.time().'&_d=1',
            ]);

        $guzzleClient->shouldReceive('request')
            ->once()
            ->withArgs([
                'GET', 'https://trk.kissmetrics.com/e?_n=Runned%20test%202&_p=example2%40example.com&_k=test&_t='.time().'&_d=1',
            ]);

        $guzzleClient->shouldReceive('request')
            ->once()
            ->withArgs([
                'GET', 'https://trk.kissmetrics.com/s?property=value_1&_k=test&_p=example3%40example.com&_t='.time().'&_d=1',
            ]);

        $this->kmTransport = new Delayed('https://trk.kissmetrics.com', $guzzleClient);
        $this->kmTransport->setLogDir('/tmp');

        $this->createAndFillQueryLogfile();

        $this->kmTransport->sendLoggedData();
    }

    protected function createAndFillQueryLogFile(): void
    {
        @unlink('/tmp/kissmetrics_query.log');

        $this->kmApi = new \KISSmetrics\Client(
            'test',
            $this->kmTransport
        );

        $this->kmApi
            ->identify('example@example.com')
            ->record('Runned test');

        $this->kmApi
            ->identify('example2@example.com')
            ->record('Runned test 2')
            ->submit();

        $this->kmApi = new \KISSmetrics\Client(
            'test',
            $this->kmTransport
        );

        $this->kmApi
            ->identify('example3@example.com')
            ->set([
                'property' => 'value_1',
            ])
            ->submit();
    }

    public function tearDown()
    {
        if ($container = m::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        m::close();
    }
}
