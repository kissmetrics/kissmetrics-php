<?php
/**
 * Redis transport aggregates events in Redis and sends them all together.
 * Usually via crontab or other similar means.
 */

namespace KISSmetrics\Transport;

/**
 * Redis transport implementation.
 *
 * Logs all events to Redis and provides method for loading and
 * sending all logged events to KISSmetrics via KISSmetrics\Transport\Sockets.
 *
 * This allows for many events to be gathered and then sent all at once instead
 * of opening a new connection to the KISSmetrics API for every event.
 *
 * To ship logged events to KISSMetrics:
 *
 * @code
 * $log_dir = '/path/to/directory';
 * $km_transport = KISSmetrics\Transport\Redis::initDefault($redis_host, $redis_port, $redis_prefix);
 * $km_transport->sendLoggedData();
 * @endcode
 *
 * @author Joe Shindelar <eojthebrave@gmail.com>
 */
class DelayedRedis extends Sockets implements Transport {

  /**
   * The host of the Redis instance.
   * @var string
   */
  protected $redis_host;

  /**
   * The port of the Redis instance.
   * @var int
   */
  protected $redis_port;

  /**
   * The Redis database to use.
   * @var int
   */
  protected $redis_database;

  /**
   * The prefix used for the Redis key.
   * @var string
   */
  protected $redis_prefix;

  /**
   * Unix timestamp of current request.
   * @var null|int
   */
  static $epoch = NULL;

  /**
   * The Redis instance
   * @var \Redis
   */
  private $redis_instance;

  /**
   * Constructor
   *
   * @param string $redis_host
   *   Host of the Redis instance where event logs are stored.
   * @param int $redis_port
   *   Port of the Redis instance where event logs are stored.
   * @param int $redis_database
   *  The database index to use for storing event logs.
   * @param string $redis_prefix
   *   Port of the Redis instance where event logs are stored.
   * @param string $host
   *   HTTP host to use when connecting to the KISSmetrics API.
   * @param int $port
   *   HTTP port to use when connecting to the KISSmetrics API.
   * @param int $timeout
   *   Number of seconds to wait before timing out when connecting to the
   *   KISSmetrics API.
   */
  public function __construct($redis_host = '127.0.0.1', $redis_port = 6379, $redis_database = 0, $redis_prefix = 'KISSmetrics', $host, $port, $timeout = 30) {
    parent::__construct($host, $port, $timeout);
    $this->redis_host = $redis_host;
    $this->redis_port = $redis_port;
    $this->redis_database = $redis_database;
    $this->redis_prefix = $redis_prefix;
  }

  /**
   * Create new instance of KISSmeterics\Transport\Redis with defaults set.
   *
   * @param string $redis_host
   *   Host of the Redis instance where event logs are stored.
   * @param int $redis_port
   *   Port of the Redis instance where event logs are stored.
   * @param int $redis_database
   *  The database index to use for storing event logs.
   * @param string $redis_prefix
   *   Port of the Redis instance where event logs are stored.
   *
   * @return \KISSmetrics\Transport\Redis
   */
  public static function initDefault($redis_host = '127.0.0.1', $redis_port = 6379, $redis_database = 0, $redis_prefix = 'KISSmetrics') {
    return new static($redis_host, $redis_port, $redis_database, $redis_prefix, 'trk.kissmetrics.com', 80);
  }

  /**
   * Get the Redis host
   *
   * @return string
   */
  public function getRedisHost() {
    return $this->redis_host;
  }

  /**
   * Get the Redis port
   * @return int
   */
  public function getRedisPort() {
    return $this->redis_port;
  }

  /**
   * Get the Redis prefix
   *
   * @return string
   */
  public function getRedisPrefix() {
    return $this->redis_prefix;
  }

  /**
   * Get the Redis database
   *
   * @return int
   */
  public function getRedisDatabase() {
    return $this->redis_database;
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
   * Log queries to Redis so they can be sent to KISSmetrics later.
   *
   * @see Transport
   */
  public function submitData(array $queries) {
    $queryLogKey = $this->getRedisPrefix().'_events';

    foreach ($queries as $key => $query) {
      // Keep timestamps when batching things via cron, or if they're manually
      // specified.
      $queries[$key][1]['_d'] = TRUE;
      if (!array_key_exists('_t', $queries[$key][1])) {
        $queries[$key][1]['_t'] = self::epoch();
      }
      try {
        $this->pushToRedis($queryLogKey, serialize($queries[$key]));
      }catch(Exception $e) {
        throw new TransportException("Cannot write to the KISSmetrics Redis event log: " . $e->getMessage());
      }
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
    $queryLogKey = $this->getRedisPrefix().'_events';
    // Load all stored queries.
    $data = $this->getFromRedis($queryLogKey);

    // Unserialize all the queries into a single array.
    $all_queries = array();
    foreach ($data as $serialized_queries) {
      if($serialized_queries !== null && strlen($serialized_queries) !== 0) {
        $queries = unserialize($serialized_queries);
        array_push($all_queries, $queries);
      }
    }

    try {
      // Send all the stored queries using the KISSmetrics/Transport/Sockets
      // implementation.
      parent::submitData($all_queries);

      // Cleanup the queries log in Redis
      $this->cleanupRedis($queryLogKey);

    }
    catch (Exception $e) {
      throw new TransportException("Cannot send logged events to KISSmetrics: " . $e->getMessage());
    }
  }

  /**
   * Initialize the \Redis instance
   */
  private function initRedis() {
    /** @var \Redis $redis */
    $this->redis_instance = new \Redis();
    $this->redis_instance->connect($this->getRedisHost(), $this->getRedisPort());
    $this->redis_instance->select($this->getRedisDatabase());
  }

  /**
   * Push a query value to Redis
   * @param string $key the query log key
   */
  private function pushToRedis($key, $value) {
    if($this->redis_instance === null) {
      $this->initRedis();
    }
    $this->redis_instance->rPush($key, $value);
  }

  /**
   * Get all the full query log from Redis
   * @param string $key the query log key
   * @return array the full query log
   */
  private function getFromRedis($key) {
    if($this->redis_instance === null) {
      $this->initRedis();
    }
    return $this->redis_instance->lRange($key, 0, -1);
  }

  /**
   * Clean up all the query logs
   * @param string $key the query log key
   */
  private function cleanupRedis($key) {
    return $this->redis_instance->delete($key);
  }

  /**
   * Set a \Redis instance
   * $redis_instance \Redis the \Redis instance to set
   */
  public function setRedisInstance($redis_instance) {
    $this->redis_instance = $redis_instance;
  }
}
