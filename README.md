KISSmetrics for PHP
===================

[![Build Status](https://travis-ci.org/kissmetrics/kissmetrics-php.png?branch=master)](https://travis-ci.org/kissmetrics/kissmetrics-php)

KISSmetrics PHP client that doesn't overuse the singleton pattern and has a
slightly better API and no built-in cron support (that's a feature). Here's
how to use it:

```php
$km = new KISSmetrics\Client('API key', KISSmetrics\Transport\Sockets::initDefault()); // Initialize

$km->identify('bob@example.com')   // Identify user (always)
  ->alias('old-anonymous-cookie')  // Alias to previously anonymous user, maybe
  ->set(array('gender' => 'male')) // Set some property
  ->record('Viewed thing');        // Record an event, optionally with properties

$km->submit(); // Submit all that to KISSmetrics in one go
```

In case of errors this thing throws a `KISSmetrics\ClientException` so if you
have a fire-and-forget attitude to these metrics just try/catch those. Though
it's helpful when you want to make sure everything is setup correctly!

### Composer

```json
{
  "require": {
    "kissmetrics/kissmetrics-php": "~0.2.0"
  }
}
```

### License

Licensed under the MIT license.
