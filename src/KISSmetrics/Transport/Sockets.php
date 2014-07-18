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

namespace KISSmetrics\Transport;

/**
 * Socket transport (fsockets) implementation
 *
 * @author Eugen Rochko <eugen@zeonfederated.com>
 */

class Sockets implements Transport {
  /**
   * Host
   * @var string
   */
  protected $host;

  /**
   * Port
   * @var integer
   */
  protected $port;

  /**
   * Request timeout
   * @var integer
   */
  protected $timeout;

  /**
   * Constructor
   * @param string  $host
   * @param integer $port
   * @param integer $timeout
   */
  public function __construct($host, $port, $timeout = 30) {
    $this->host    = $host;
    $this->port    = $port;
    $this->timeout = $timeout;
  }

  /**
   * Create new instance with KISSmetrics API defaults
   * @return Sockets
   */
  public static function initDefault() {
    return new static('trk.kissmetrics.com', 80);
  }

  /**
   * Get host
   * @return string
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * Get port
   * @return integer
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * @see Transport
   */
  public function submitData(array $queries) {
    $fp = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

    if(! $fp) {
      throw new TransportException("Cannot connect to the KISSmetrics server: " . $errstr);
    }

    //stream_set_blocking($fp, 0);

    $i = 0;

    foreach($queries as $data) {
      $query = http_build_query($data[1], '', '&');
      $query = str_replace(
                  array('+', '%7E'),
                  array('%20', '~'),
                  $query
               );

      $req  = 'GET /' . $data[0] . '?' . $query . ' HTTP/1.1' . "\r\n";
      $req .= 'Host: ' . $this->host . "\r\n";

      if(++$i == count($queries)) {
        $req .= 'Connection: Close' . "\r\n\r\n";
      } else {
        $req .= 'Connection: Keep-Alive' . "\r\n\r\n";
      }

      $written = fwrite($fp, $req);

      if($written === false) {
        throw new TransportException("Could not submit the query: /" . $data[0] . "?" . $query);
      }
    }

    fclose($fp);
  }
}
