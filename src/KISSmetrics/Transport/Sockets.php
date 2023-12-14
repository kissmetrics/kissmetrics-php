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
    return new static('trk.kissmetrics.io', 80);
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
    foreach ($queries as $data) {
        $ch = curl_init(); // Initialize cURL handle inside the loop

        $query = http_build_query($data[1], '', '&');
        $query = str_replace(['+', '%7E'], ['%20', '~'], $query);

        $url = 'http://' . $this->host . ':' . $this->port . '/' . $data[0] . '?' . $query;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Keep-Alive']);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            throw new TransportException("cURL error: " . curl_error($ch));
        }
        echo $url. '<br>';
        curl_close($ch); // Close the cURL handle at the end of each loop iteration
    }
  }
}
