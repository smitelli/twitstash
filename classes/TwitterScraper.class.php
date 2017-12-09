<?php

  /**
   * Twitter Scraper Class. Provides an interface to iterate over "pages" of the
   * Twitter API and return relevant data from each tweet in a custom format.
   * @author Scott Smitelli
   * @package twitstash
   */

  class TwitterScraper {
    private $config;
    private $twitter;
    private $placeCache;
    private $urlCache;
    private $lowID;

    /**
     * Constructor function. Parses a config array for 'consumer_key',
     * 'consumer_secret', 'access_token', and 'access_token_secret' keys.
     * @access public
     * @param array $config The configuration array
     */
    public function __construct($config) {
      $this->config  = (object) $config;
      $this->twitter = new TwitterOAuth(
        $this->config->consumer_key,
        $this->config->consumer_secret,
        $this->config->access_token,
        $this->config->access_token_secret
      );
      $this->placeCache = array();
      $this->urlCache   = array();
      $this->resetLowID();
    }

    /**
     * Returns the current cache of every "place" this object has encountered
     * since it was instantiated.
     * @access public
     * @return array An array of "place" objects
     */
    public function getPlaceCache() {
      return $this->placeCache;
    }

    /**
     * Returns the current cache of every URL this object has encountered since
     * it was instantiated.
     * @access public
     * @return array An array of URL objects
     */
    public function getURLCache() {
      return $this->urlCache;
    }

    /**
     * Returns the lowest ID number that has been encountered since the last
     * time resetLowID() was called. This ID is represented as a string!
     * @access public
     * @return string The lowest ID seen so far
     */
    public function getLowID() {
      return $this->lowID;
    }

    /**
     * Resets the "lowest" ID number by setting it to the highest possible ID
     * that Twitter seems to be able to return.
     * @access public
     */
    public function resetLowID() {
      // The highest conceivable tweet ID (2^63 - 2), found with trial and error
      $this->lowID = bcsub(bcpow('2', '63'), '2');
    }

    /**
     * Downloads a list of tweets from Twitter. The tweets will all be older
     * than the max ID seen so far. As tweets are loaded, the max ID will be
     * decremented accordingly. Any "place" or URL objects encountered will be
     * cached for later use. The resulting custom tweet objects will be returned
     * in an array.
     * @access public
     * @return array Custom tweet objects containing data from this run
     */
    public function fetchTimeline() {
      // Query Twitter for the list of recent tweets from the specified user
      $tweets = $this->twitter->get('https://api.twitter.com/1.1/statuses/user_timeline.json', array(
        'screen_name'         => $this->config->screen_name,
        'count'               => 200,
        'max_id'              => $this->lowID,
        'trim_user'           => TRUE,
        'exclude_replies'     => FALSE,
        'contributor_details' => TRUE,
        'include_rts'         => TRUE,
        'tweet_mode'          => 'extended'
      ));

      $data = array();
      if (is_array($tweets) && count($tweets) > 0) {
        // We have tweets; loop through them all
        foreach ($tweets as $tweet) {
          // Custom tweet object
          $tmp = (object) array(
            'id'         => $tweet->id_str,
            'created_at' => date('Y-m-d H:i:s', strtotime($tweet->created_at)),
            'text'       => html_entity_decode($tweet->full_text),
            'source'     => $tweet->source,
            'reply_id'   => $tweet->in_reply_to_status_id_str ?: 0,
            'rt_id'      => 0,
            'place_id'   => '',
            'latitude'   => 0,
            'longitude'  => 0
          );

          // Track the lowest tweet ID that has been encountered
          if (bccomp($this->lowID, $tmp->id) > 0) {
            $this->lowID = bcsub($tmp->id, '1');
          }

          // If this is a retweet, grab the original tweet's text instead
          if (isset($tweet->retweeted_status) && is_object($tweet->retweeted_status)) {
            $tmp->text     = html_entity_decode($tweet->retweeted_status->full_text);
            $tmp->reply_id = $tweet->retweeted_status->in_reply_to_status_id_str ?: 0;
            $tmp->rt_id    = $tweet->retweeted_status->id_str;
          }

          // If this tweet has a "place" ID, store the data that comes with it
          if (isset($tweet->place) && is_object($tweet->place)) {
            $tmp->place_id = $tweet->place->id;
            $this->cachePlaceData($tweet->place);
          }

          // If this tweet has point coordinates, store them
          if (isset($tweet->coordinates) && is_object($tweet->coordinates)) {
            $tmp->longitude = $tweet->coordinates->coordinates[0];
            $tmp->latitude  = $tweet->coordinates->coordinates[1];
          }

          // If this tweet references any t.co URLs or images, store them
          if (isset($tweet->entities->urls) && is_array($tweet->entities->urls)) {
            $this->cacheURLData($tweet->entities->urls);
          }
          if (isset($tweet->entities->media) && is_array($tweet->entities->media)) {
            $this->cacheURLData($tweet->entities->media);
          }

          $data[] = $tmp;
        }
      }

      return $data;
    }

    /**
     * When a "place" object is encountered, store the important data in a
     * custom object and append it to the respective cache array for later use.
     * @access private
     */
    private function cachePlaceData(&$place) {
      // Attempt to find the "centroid" of this place by averaging all the found
      // lat/lon pairs together and treating that average as a point.
      $centroid = (object) array(
        'latitude'  => 0,
        'longitude' => 0,
        'counter'   => 0,
      );
      foreach ($place->bounding_box->coordinates as $polygon) {  //single bounding box...
        foreach ($polygon as $point) {  //single point within this bounding box
          $centroid->longitude += $point[0];
          $centroid->latitude  += $point[1];
          $centroid->counter++;
        }
      }
      if ($centroid->counter > 0) {  //don't divide by zero
        $centroid->latitude  /= $centroid->counter;
        $centroid->longitude /= $centroid->counter;
      }

      // Custom "place" object
      $this->placeCache[$place->id] = (object) array(
        'place_type'   => $place->place_type,
        'full_name'    => $place->full_name,
        'country'      => $place->country,
        'centroid_lat' => $centroid->latitude,
        'centroid_lon' => $centroid->longitude
      );
    }

    /**
     * When a URL object is encountered, store the important data in a custom
     * object and append it to the respective cache array for later use.
     * @access private
     */
    private function cacheURLData(&$urls) {
      foreach ($urls as $url) {
        // Custom URL object
        $this->urlCache[] = (object) array(
          'url'          => $url->url,
          'expanded_url' => $url->expanded_url
        );
      }
    }
  }

?>
