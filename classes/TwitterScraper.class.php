<?php

  class TwitterScraper {
    private $config;
    private $twitter;
    private $placeCache;
    private $urlCache;
    private $lowID;

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

    public function getPlaceCache() {
      return $this->placeCache;
    }

    public function getURLCache() {
      return $this->urlCache;
    }
    
    public function getLowID() {
      return $this->lowID;
    }

    public function resetLowID() {
      // The highest conceivable tweet ID (2^63 - 2), found with trial and error
      $this->lowID = bcsub(bcpow('2', '63'), '2');
    }

    public function fetchTimeline() {
      // Query Twitter for the list of recent tweets from the specified user
      $tweets = $this->twitter->get('https://api.twitter.com/1.1/statuses/user_timeline.json', array(
        'screen_name'         => $this->config->screen_name,
        'count'               => 200,
        'max_id'              => $this->lowID,
        'trim_user'           => TRUE,
        'exclude_replies'     => FALSE,
        'contributor_details' => TRUE,
        'include_rts'         => TRUE
      ));

      $data = array();
      if (is_array($tweets) && count($tweets) > 0) {
        // We have tweets; loop through them all
        foreach ($tweets as $tweet) {
          // Custom tweet object
          $tmp = (object) array(
            'id'         => $tweet->id_str,
            'created_at' => date('Y-m-d H:i:s', strtotime($tweet->created_at)),
            'text'       => html_entity_decode($tweet->text),
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
            $tmp->text     = html_entity_decode($tweet->retweeted_status->text);
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

    private function cachePlaceData(&$place) {
      // Attempt to find the "centroid" of this place by averaging all the found
      // lat/lon pairs together and treating that average as a point.
      $centroid = (object) array(
        'latitude'  => 0,
        'longitude' => 0,
        'counter'   => 0,
      );
      foreach ($place->bounding_box->coordinates as $polygon) {
        foreach ($polygon as $point) {
          $centroid->longitude += $point[0];
          $centroid->latitude  += $point[1];
          $centroid->counter++;
        }
      }
      if ($centroid->counter > 0) {
        $centroid->latitude  /= $centroid->counter;
        $centroid->longitude /= $centroid->counter;
      }

      $this->placeCache[$place->id] = (object) array(
        'place_type'   => $place->place_type,
        'full_name'    => $place->full_name,
        'country'      => $place->country,
        'centroid_lat' => $centroid->latitude,
        'centroid_lon' => $centroid->longitude
      );
    }

    private function cacheURLData(&$urls) {
      foreach ($urls as $url) {
        $this->urlCache[] = (object) array(
          'url'          => $url->url,
          'expanded_url' => $url->expanded_url
        );
      }
    }
  }

?>