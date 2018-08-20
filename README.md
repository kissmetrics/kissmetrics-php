KISSmetrics for PHP
===================

[![CircleCI](https://circleci.com/gh/Learnfield-GmbH/kissmetrics-php.svg?style=svg&circle-token=c023d1ac79ca1fd9710f28d45de1aae32c7b292c)](https://circleci.com/gh/Learnfield-GmbH/kissmetrics-php)
[![Maintainability](https://api.codeclimate.com/v1/badges/b772b59abbbec9fceeb5/maintainability)](https://codeclimate.com/github/Learnfield-GmbH/kissmetrics-php/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/b772b59abbbec9fceeb5/test_coverage)](https://codeclimate.com/github/Learnfield-GmbH/kissmetrics-php/test_coverage)

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
