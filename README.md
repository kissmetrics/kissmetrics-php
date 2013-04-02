KISSmetrics PHP class that doesn't overuse the singleton pattern and
has a slightly better API and no built-in cron support (that's a
feature). Here's how to use it:

    <?php
    
    $km = new KM('API key'); // Initialize

    $km->identify('bob@example.com')   // Identify user
      ->alias('old-anonymous-cookie')  // Alias to anonymous user
      ->set(array('gender' => 'male')) // Set a property
      ->record('Viewed thing');        // Record an event

    $km->submit(); // Submit all that to KISSmetrics in one go

Cheers,  
Eugen
