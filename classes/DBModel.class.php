<?php

  class DBModel extends Database {
    public function __construct($config) {
      // Connect to the DB
      parent::__construct($config);
    }
    
    public function highestTweetID() {
      // Find the highest 'id' field
      self::db_query('SELECT max(`id`) as `max_id` FROM `tweets`');
      return $this->db_fetch()->max_id ?: 0;
    }
    
    public function resetTouched() {
      // Set entire 'touched' column to 0
      self::db_exec_single("UPDATE `tweets` SET `touched` = 0");
    }
    
    public function deleteUntouchedSince($lowID) {
      // Mark tweets as deleted if they were not touched by this run
      self::db_query('UPDATE `tweets` SET `deleted` = NOW() WHERE `touched` = 0 AND id > :lowID', array('lowID', $lowID));
      self::resetTouched();
    }
    
    public function tweetInsert($tweets) {
      foreach ($tweets as $tweet) {
        // Insert a new tweet into the DB, or mark a seen 
        self::db_query('
          INSERT INTO `tweets` (`id`, `created_at`, `text`, `source`, `reply_id`, `rt_id`, `place_id`, `latitude`, `longitude`, `touched`, `deleted`)
          VALUES(:id, :created_at, :text, :source, :reply_id, :rt_id, :place_id, :latitude, :longitude, 1, NULL)
          ON DUPLICATE KEY UPDATE `touched` = 1
        ', $tweet);
      }
    }
    
    public function placeInsert($place) {
      // insert into DB, or update on dupe key
    }
    
    public function urlInsert($url) {
      // insert into DB, or update on dupe key
    }
  }

?>