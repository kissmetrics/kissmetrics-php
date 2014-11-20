<?php

/**
 * Copyright (c) 2013 Eugen Rochko
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace KISSmetrics;
use KISSmetrics\Transport\Transport;

/**
 * KISSmetrics PHP API class that doesn't abuse the singleton pattern
 * and whose methods can be chained
 *
 * @author Eugen Rochko <eugen@zeonfederated.com>
 */

class Client {
  /**
   * API key
   * @var string
   */
  private $key;

  /**
   * User identification
   * @var string
   */
  private $id;

  /**
   * Queries queued for submission
   * @var array
   */
  private $queries;

  /**
   * Transport instance
   * @var Transport
   */
  protected $transport;

  /**
   * Initialize
   * @param string $key
   */
  public function __construct($key, Transport $transport)
  {
    $this->key       = $key;
    $this->queries   = array();
    $this->transport = $transport;
  }

  /**
   * Get API key
   * @return string
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * Identify user
   * @param  string $id
   * @return Client
   */
  public function identify($id)
  {
    $this->id = $id;
    return $this;
  }

  /**
   * Get identification
   * @return string
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Alias an alternative name to the currently identified user
   * @param  string $old_id
   * @return Client
   */
  public function alias($old_id)
  {
    $this->ensureSetup();

    array_push($this->queries, array(
      'a',
      array(
        '_p' => $old_id,
        '_n' => $this->id,
        '_k' => $this->key
      )
    ));

    return $this;
  }

  /**
   * Record an event with properties
   * @param  string  $event
   * @param  array   $properties
   * @param  integer $time
   * @return Client
   */
  public function record($event, $properties = array(), $time = null)
  {
    $this->ensureSetup();

    if(is_null($time)) {
      $time = time();
      $isManualTime = false;
    } else {
      $isManualTime = true;
    }

    array_push($this->queries, array(
      'e',
      array_merge($properties, array(
        '_n' => $event,
        '_p' => $this->id,
        '_k' => $this->key,
        '_t' => $time,
        '_d' => $isManualTime
      ))
    ));

    return $this;
  }

  /**
   * Set a property on the user
   * @param  array   $properties
   * @param  integer $time
   * @return Client
   */
  public function set($properties, $time = null)
  {
    $this->ensureSetup();

    if(is_null($time)) {
      $time = time();
      $isManualTime = false;
    } else {
      $isManualTime = true;
    }

    array_push($this->queries, array(
      's',
      array_merge($properties, array(
        '_k' => $this->key,
        '_p' => $this->id,
        '_t' => $time,
        '_d' => $isManualTime
      ))
    ));

    return $this;
  }

  /**
   * Ensure that all data is setup before doing any things
   * @throws ClientException If the API key or the user ID are not set
   * @return void
   */
  private function ensureSetup()
  {
    if(is_null($this->key)) {
      throw new ClientException("KISSmetrics API key not specified");
    }

    if(is_null($this->id)) {
      throw new ClientException("KISSmetrics user not identified yet");
    }
  }

  /**
   * Get queued events
   * @return array
   */
  public function getQueries() {
    return $this->queries;
  }

  /**
   * Submit the things to the remote host
   * @return void
   */
  public function submit()
  {
    $this->transport->submitData($this->queries);
  }
}
