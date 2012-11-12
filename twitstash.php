<?php

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

  // These classes do all the gruntwork here
  $twitter  = new TwitterScraper($config['twitter']);
  $database = new DBModel($config['mysql']);

  while ($tweets = $twitter->fetchTimeline()) {
    //print_r($tweets);
    //echo $twitter->getLowID();
  }
  
  //print_r($twitter->getPlaceCache());
  //print_r($twitter->getURLCache());

?>