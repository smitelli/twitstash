<?php

  /**
   * twitstash: Tweetin' care of business and stashing overtime.
   *
   * @author Scott Smitelli
   * @package twitstash
   */

  // Support autoloading classes as they are needed
  define('APP_DIR', realpath(dirname(__FILE__)));
  spl_autoload_register(function($class_name) {
    $class_file = sprintf(APP_DIR . "/classes/{$class_name}.class.php");
    if (is_readable($class_file)) {
      // File exists; load it
      require_once $class_file;
    }
  });

  // Load and parse the configuration file
  $config = @parse_ini_file(APP_DIR . '/config.ini', TRUE);
  if (empty($config)) {
    die("The file config.ini is missing or malformed.\n\n");
  }
  date_default_timezone_set($config['misc']['timezone']);

  // These classes do all the gruntwork for this script
  $twitter  = new TwitterScraper($config['twitter']);
  $database = new DBModel($config['mysql']);

  // Ensure the DB is in a good state; find the most recently stored ID
  $database->resetTouched();
  $stop_at = $database->getHighID();

  // Go through every "page" of API results
  $page = 0;
  while ($tweets = $twitter->fetchTimeline()) {
    echo 'Reading page ' . (++$page) . "...\n";
    $database->insertTweets($tweets);

    // If we've seen an ID that the DB already has, stop making requests
    if ($twitter->getLowID() < $stop_at) break;
  }

  // Mark tweets that have "gone away" as being deleted
  $database->deleteUntouchedSince($twitter->getLowID());

  // Send all places and URLs seen during API parsing to the DB
  $database->insertPlaces($twitter->getPlaceCache());
  $database->insertURLs($twitter->getURLCache());

  echo "Done.\n";

?>
