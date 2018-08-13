<?php
/**
 * Delayed transport aggregates events locally and sends them all together.
 * Usually via crontab or other similar means.
 */

namespace KISSmetrics\Transport;

/**
 * Delayed transport implementation.
 *
 * Logs all events to the local file system and provides method for loading and
 * sending all logged events to KISSmetrics via KISSmetrics\Transport\Sockets.
 *
 * This allows for many events to be gathered and then sent all at once instead
 * of opening a new connection to the KISSmetrics API for every event.
 *
 * To ship logged events to KISSMetrics:
 *
 * @code
 * $log_dir = '/path/to/directory';
 * $km_transport = KISSmetrics\Transport\Delayed::initDefault($log_dir);
 * $km_transport->sendLoggedData();
 * @endcode
 *
 * @author Joe Shindelar <eojthebrave@gmail.com>
 */
class Delayed extends Sockets implements Transport {

  /**
   * Directory where logged events should be stored.
   * @var string
   */
  protected $log_dir;

  /**
   * @var string
   */
  protected $log_filename = 'kissmetrics_query.log';

  /**
   * Unix timestamp of current request.
   * @var null|int
   */
  static $epoch = NULL;

  /**
   * Constructor
   *
   * @param string $log_dir
   *   Full path to local file system directory where event logs are stored.
   * @param string $host
   *   HTTP host to use when connecting to the KISSmetrics API.
   * @param int $port
   *   HTTP port to use when connecting to the KISSmetrics API.
   * @param int $timeout
   *   Number of seconds to wait before timing out when connecting to the
   *   KISSmetrics API.
   */
  public function __construct($host, $port, $timeout = 30) {
    parent::__construct($host, $port, $timeout);
  }

  /**
   * Create new instance of KISSmeterics\Transport\Delayed with defaults set.
   *
   * @param string $log_dir
   *   Full path to local file system directory where event logs are stored.
   *
   * @return \KISSmetrics\Transport\Delayed
   */
  public static function initDefault() {
    return new static('trk.kissmetrics.com', 80);
  }

  /**
   * Set the log directory
   *
   * @param string $log_dir
   */
  public function setLogDir($log_dir)
  {
      $this->log_dir = $log_dir;
  }

  /**
   * Get log directory.
   *
   * @return string
   */
  public function getLogDir() {
    return $this->log_dir;
  }

  /**
   * Get the full path to the log file.
   *
   * @return string
   */
  protected function getLogFile() {
    $log_dir = $this->getLogDir();
    if (empty($log_dir)) {
        throw new TransportException('Cannot get log file, location not provided');
    }

    // Prefent file_not_found erorrs since the methods submitData and sendLoggedData don't check if the file exists
    if (!file_exists($log_dir . '/' . $this->log_filename)) {
        touch($log_dir . '/' . $this->log_filename);
    }

    return $log_dir . '/'.$this->log_filename;
  }

  /**
   * Get the stored timestamp for this request or generate it if not set.
   *
   * @return int
   *   UNIX timestamp.
   */
  static protected function epoch() {
    if (self::$epoch) {
      return self::$epoch;
    }

    return time();
  }

  /**
   * Log queries to a local file so they can be sent to KISSmetrics later.
   *
   * @see Transport
   */
  public function submitData(array $queries) {
    foreach ($queries as $key => $query) {
      // Keep timestamps when batching things via cron, or if they're manually
      // specified.
      $queries[$key][1]['_d'] = TRUE;
      if (!array_key_exists('_t', $queries[$key][1])) {
        $queries[$key][1]['_t'] = self::epoch();
      }
    }

    try {
      // Store our queries as a serialized array on a newline in the log file.
      $fh = fopen(self::getLogFile(),'a');
      if($fh) {
        fputs($fh, serialize($queries) . "\n");
        fclose($fh);
      }
    }
    catch(Exception $e) {
      throw new TransportException("Cannot write to the KISSmetrics event log: " . $e->getMessage());
    }
  }

  /**
   * Use the Sockets transport implmentation to send logged data to KISSmetrics.
   *
   * Loads the contents of the log file and then sends it to KISSmetrics for
   * processing. If successful deletes the log file afterwards so that we do
   * not send duplicate events.
   *
   * @throws TransportException
   */
  public function sendLoggedData() {
    // Load all stored queries.
    $data = file_get_contents($this->getLogFile());
    $data = explode('\n', $data);

    // Unserialize all the queries into a single array.
    $all_queries = array();
    foreach ($data as $serialized_queries) {
      $queries = unserialize($serialized_queries);
      if ($queries !== false) {
          $all_queries += $queries;
      }
    }

    if (count($all_queries) === 0) {
      return;
    }

    try {
      // Send all the stored queries using the KISSmetrics/Transport/Sockets
      // implementation.
      parent::submitData($all_queries);

      // Cleanup the log file so we don't resend the same data again.
      unlink($this->getLogFile());
    }
    catch (Exception $e) {
      throw new TransportException("Cannot send logged events to KISSmetrics: " . $e->getMessage());
    }
  }
}
