<?php

  class DBModel extends Database {
    public function __construct($config) {
      // Connect to the DB
      parent::__construct($config);
    }
    
    public function tweetHighestID() {
      // return the highest 'id' field
    }
    
    public function tweetPreActions() {
      // set entire 'touched' column 0
    }
    
    public function tweetPostActions() {
      // set 'deleted' to NOW() where touched = 0 and id > getLowID()
    }
    
    public function tweetInsert($tweets) {
      foreach ($tweets as $tweet) {
        // insert into DB, or update on dupe key
        // set 'touched' to 1, 'deleted' to NULL
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