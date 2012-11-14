<?php

  class DBModel extends Database {
    public function __construct($config) {
      // Connect to the DB
      parent::__construct($config);
    }
    
    public function getHighID() {
      // Find the highest 'id' field in the tweet table
      self::db_query('SELECT max(`id`) as `max_id` FROM `tweets`');
      return self::db_fetch()->max_id ?: 0;
    }
    
    public function resetTouched() {
      // Mark every tweet as untouched
      self::db_exec_single("UPDATE `tweets` SET `touched` = 0");
    }
    
    public function deleteUntouchedSince($lowID) {
      // Mark tweets "deleted" if they remain untouched; reset touch status
      self::db_query('UPDATE `tweets` SET `deleted` = NOW() WHERE `id` > :lowID AND `touched` = 0 AND `deleted` IS NULL', array('lowID' => $lowID));
      self::resetTouched();
    }
    
    public function insertTweets($tweets) {
      foreach ($tweets as $tweet) {
        // Insert a new tweet into the DB, or mark an existing tweet as seen 
        self::db_query('
          INSERT INTO `tweets` (`id`, `created_at`, `text`, `source`, `reply_id`, `rt_id`, `place_id`, `latitude`, `longitude`, `touched`, `deleted`)
          VALUES(:id, :created_at, :text, :source, :reply_id, :rt_id, :place_id, :latitude, :longitude, 1, NULL)
          ON DUPLICATE KEY UPDATE `touched` = 1
        ', $tweet);
      }
    }
    
    public function insertPlaces($places) {
      foreach ($places as $id => $place) {
        // Insert a new place into the DB, or freshen its info
        $place->id = $id;
        self::db_query('
          REPLACE INTO `places` (`id`, `place_type`, `full_name`, `country`, `centroid_lat`, `centroid_lon`)
          VALUES(:id, :place_type, :full_name, :country, :centroid_lat, :centroid_lon)
        ', $place);
      }
    }
    
    public function insertURLs($urls) {
      foreach ($urls as $url) {
        // Insert a new URL into the DB, or freshen its info
        self::db_query('REPLACE INTO `urls` (`url`, `expanded_url`) VALUES(:url, :expanded_url)', $url);
      }
    }
  }

?>