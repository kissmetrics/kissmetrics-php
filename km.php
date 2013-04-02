<?php

/**
 * KISSmetrics PHP API class that doesn't abuse the singleton pattern
 * and whose methods can be chained
 *
 * @author  Eugen Rochko <eugen@zeonfederated.com>
 */

class KM
{
  /**
   * API key
   *
   * @var string
   */

  private $key;

  /**
   * User identification
   *
   * @var string
   */

  private $id;

  /**
   * Queries queued for submission
   *
   * @var array
   */

  private $queries;

  /**
   * KISSmetrics API host
   *
   * @var string
   */

  protected $host = 'trk.kissmetrics.com';

  /**
   * KISSmetrics API port
   *
   * @var integer
   */

  protected $port = 80;

  /**
   * Initialize
   *
   * @param string $key
   */

  public function __construct($key, $host = null, $port = null)
  {
    $this->key     = $key;
    $this->queries = array();

    if(! is_null($host))
    {
      $this->host = $host;
    }

    if(! is_null($port))
    {
      $this->port = $port;
    }
  }

  /**
   * Identify user
   *
   * @param  string $id
   * @return KM
   */

  public function identify($id)
  {
    $this->id = $id;
    return $this;
  }

  /**
   * Alias an alternative name to the currently identified user
   *
   * @param  string $old_id
   * @return KM
   */

  public function alias($old_id)
  {
    $this->ensureSetup();

    array_push($this->queries, array(
      'a' => array(
        '_p' => $old_id,
        '_n' => $this->id,
        '_k' => $this->key
      )
    ));

    return $this;
  }

  /**
   * Record an event with properties
   *
   * @param string  $event
   * @param array   $properties
   * @param integer $time
   *
   * @return KM
   */

  public function record($event, $properties = array(), $time = null)
  {
    $this->ensureSetup();

    if(is_null($time))
    {
      $time = time();
    }

    array_push($this->queries, array(
      'e' => array_merge($properties, array(
        '_n' => $event,
        '_p' => $this->id,
        '_k' => $this->key,
        '_t' => $time
      ))
    ));

    return $this;
  }

  /**
   * Set a property on the user
   *
   * @param array   $properties
   * @param integer $time
   *
   * @return KM
   */

  public function set($properties, $time = null)
  {
    $this->ensureSetup();

    if(is_null($time))
    {
      $time = time();
    }

    array_push($this->queries, array(
      's' => array_merge($properties, array(
        '_k' => $this->key,
        '_p' => $this->id,
        '_t' => $time
      ))
    ));

    return $this;
  }

  /**
   * Ensure that all data is setup before doing any things
   *
   * @throws KMException If the API key or the user ID are not set
   * @return void
   */

  private function ensureSetup()
  {
    if(is_null($this->key))
    {
      throw new KMException("KISSmetrics API key not specified");
    }

    if(is_null($this->id))
    {
      throw new KMException("KISSmetrics user not identified yet");
    }
  }

  /**
   * Submit the things to the remote host
   *
   * @return void
   */

  public function submit()
  {
    $fp = fsockopen($this->host, $this->port, $errno, $errstr, 30);

    if(! $fp)
    {
      throw new KMException("Cannot connect to the KISSmetrics server");
    }

    stream_set_blocking($fp, 0);

    $i = 0;

    foreach($this->queries as $endpoint => $data)
    {
      $req  = 'GET /' . $endpoint . '?' . http_build_query($data, '', '&') . ' HTTP/1.1' . "\r\n";
      $req .= 'Host: ' . $this->host . "\r\n";

      if(++$i == count($this->queries))
      {
        $req .= 'Connection: Close' . "\r\n\r\n";
      }
      else
      {
        $req .= 'Connection: Keep-Alive' . "\r\n\r\n";
      }

      fwrite($fp, $req);
    }

    fclose($fp);
  }
}

class KMException
{}
