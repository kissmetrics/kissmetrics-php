<?php

namespace KISSmetrics\Transport;

use function str_repeat;

/**
 * Delayed transport implementation.
 *
 * Logs all events to the local file system and provides method for loading and
 * sending all logged events to KISSmetrics via KISSmetrics\Transport\Sockets.
 *
 * This allows for many events to be gathered and then sent all at once instead
 * of opening a new connection to the KISSmetrics API for every event.
 *
 * Delayed transport aggregates events locally and sends them all together.
 * Usually via crontab or other similar means.
 *
 * To ship logged events to KISSMetrics:
 *
 * @code
 * $logDir = '/path/to/directory';
 * $km_transport = KISSmetrics\Transport\Delayed::initDefault($logDir);
 * $km_transport->sendLoggedData();
 * @endcode
 *
 * @author Joe Shindelar <eojthebrave@gmail.com>
 */
class Delayed implements Transport
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzleClient;

    /***
     * @var string
     */
    protected $apiEndpoint;

    /**
     * Unix timestamp of current request.
     *
     * @var null|int
     */
    public static $epoch = null;

    /**
     * Directory where logged events should be stored.
     *
     * @var string
     */
    protected $logDir;

    /**
     * @var string
     */
    protected $log_filename = 'kissmetrics_query.log';

    public function __construct(
        string $apiEndpoint = 'https://trk.kissmetrics.local.wdnl',
        \GuzzleHttp\ClientInterface $guzzleClient = null
    ) {
        $this->apiEndpoint = $apiEndpoint;

        if ($guzzleClient === null) {
            $guzzleClient = new \GuzzleHttp\Client();
        }
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * Log queries to a local file so they can be sent to KISSmetrics later.
     *
     * @param array $queries
     *
     * @see Transport
     *
     * @throws \KISSmetrics\Transport\TransportException
     * @throws \KISSmetrics\Transport\NoQueriesException
     */
    public function submitData(array $queries): void
    {
        if (empty($queries)) {
            throw new NoQueriesException();
        }

        $queries = $this->formatQueries($queries);

        try {
            // Store our queries as a serialized array on a newline in the log file.
            $fh = fopen(self::getLogFile(), 'a');
            if ($fh) {
                fwrite($fh, serialize($queries).PHP_EOL);
                fclose($fh);
            }
        } catch (\Exception $e) {
            throw new TransportException('Cannot write to the KISSmetrics event log: '.$e->getMessage());
        }
    }

    protected function formatQueries(array $queries): array
    {
        foreach ($queries as $key => $query) {
            // Keep timestamps when batching things via cron, or if they're manually
            // specified.
            $queries[$key][1]['_d'] = true;
            if (!array_key_exists('_t', $queries[$key][1])) {
                $queries[$key][1]['_t'] = self::epoch();
            }
        }

        return $queries;
    }

    /**
     * Get the stored UNIX timestamp for this request or generate it if not set.
     *
     * @return int
     */
    protected static function epoch()
    {
        if (self::$epoch) {
            return self::$epoch;
        }

        return time();
    }

    protected function getLogFile(): string
    {
        $logDir = $this->getLogDir();
        if (empty($logDir)) {
            throw new TransportException('Cannot get log file, location not provided, please use setLogDir()');
        }

        // Prefent file_not_found erorrs since the methods submitData and sendLoggedData don't check if the file exists
        if (!file_exists($logDir.'/'.$this->log_filename)) {
            touch($logDir.'/'.$this->log_filename);
        }

        return $logDir.'/'.$this->log_filename;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function setLogDir($logDir): void
    {
        $this->logDir = $logDir;
    }

    public function sendLoggedData(): void
    {
        $data = file_get_contents($this->getLogFile());
        $data = explode(PHP_EOL, $data);

        // Unserialize all the queries into a single array.
        $allQueries = [];
        foreach ($data as $serializedQueries) {
            $queries = unserialize($serializedQueries);
            if ($queries !== false) {
                $allQueries[] = $queries;
            }
        }

        if (count($allQueries) === 0) {
            return;
        }

        try {
            foreach ($allQueries as $queryGroup) {
                foreach ($queryGroup as $queries) {
                    $query = http_build_query($queries[1], '', '&');
                    $query = str_replace(
                        ['+', '%7E'],
                        ['%20', '~'],
                        $query
                    );

                    $this->guzzleClient->request('GET', $this->apiEndpoint.'/'.$queries[0].'?'.$query);
                }
            }

            // Cleanup the log file so we don't resend the same data again.
            unlink($this->getLogFile());
        } catch (\Exception $e) {
            throw new TransportException('Cannot send logged events to KISSmetrics: '.$e->getMessage());
        }
    }
}
