<?php

  /**
   * Class which handles converting custom Twitter objects into SQL
   * representations and storing them in the database.
   * @author Scott Smitelli
   * @package twitstash
   */

  class DBModel extends Database {
    /**
     * Constructor function. Passes the configuration directly to the Database
     * base class to allow it to read the DB connection info.
     * @access public
     * @param array $config The configuration array
     */
    public function __construct($config) {
      // Connect to the DB
      parent::__construct($config);
    }

    /**
     * Finds the highest tweet ID number that has ever been stored in this DB.
     * @access public
     * @return string The highest ID
     */
    public function getHighID() {
      // Find the highest 'id' field in the tweet table
      self::db_query('SELECT max(`id`) as `max_id` FROM `tweets`');
      return self::db_fetch()->max_id ?: '0';
    }

    /**
     * Resets the "touched" status of every tweet (marks them as "untouched.")
     * @access public
     */
    public function resetTouched() {
      // Mark every tweet as untouched
      self::db_exec_single("UPDATE `tweets` SET `touched` = 0");
    }

    /**
     * For every tweet newer than $lowID that has not been "touched," set the
     * `deleted` column to the current date/time. Resets "touched" status.
     * @access public
     * @param string $lowID Only tweets newer than this will be affected
     */
    public function deleteUntouchedSince($lowID) {
      // Mark tweets "deleted" if they remain untouched; reset touch status
      self::db_query('UPDATE `tweets` SET `deleted` = NOW() WHERE `id` > :lowID AND `touched` = 0 AND `deleted` IS NULL', array('lowID' => $lowID));
      self::resetTouched();
    }

    /**
     * Adds new tweets into the database and marks them as "touched." If a tweet
     * is already present, mark it as "touched" only.
     * @access public
     * @param array $tweets An array of custom tweet objects
     */
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

    /**
     * Adds new "places" into the database. If a place is already present, the
     * info is replaced with the newer (presumably fresher) data.
     * @access public
     * @param array $places An array of custom "place" objects
     */
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

    /**
     * Adds new URLs into the database. If a URL is already present, the info is
     * replaced with the newer (presumably fresher) data.
     * @access public
     * @param array $urls An array of custom URL objects
     */
    public function insertURLs($urls) {
      foreach ($urls as $url) {
        // Insert a new URL into the DB, or freshen its info
        self::db_query('REPLACE INTO `urls` (`url`, `expanded_url`) VALUES(:url, :expanded_url)', $url);
      }
    }
  }

?>
