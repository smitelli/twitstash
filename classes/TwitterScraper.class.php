<?php

  class TwitterScraper {
    public  $lowID;
    private $config;
    private $twitter;

    public function __construct($config) {
      $this->config  = (object) $config;
      $this->twitter = new TwitterOAuth(
        $this->config->consumer_key,
        $this->config->consumer_secret,
        $this->config->access_token,
        $this->config->access_token_secret
      );
      $this->resetLowID();
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
            'rt_id'      => 0,
            'reply_id'   => $tweet->in_reply_to_status_id_str ?: 0,
            'created_at' => date('Y-m-d H:i:s', strtotime($tweet->created_at)),
            'text'       => $tweet->text,
            'source'     => $tweet->source,
          );

          // Track the lowest tweet ID that has been encountered
          if (bccomp($this->lowID, $tmp->id) > 0) {
            $this->lowID = bcsub($tmp->id, '1');
          }

          // If this is a retweet, grab the original tweet's text
          if (isset($tweet->retweeted_status)) {
            $tmp->rt_id    = $tweet->retweeted_status->id_str;
            $tmp->reply_id = $tweet->retweeted_status->in_reply_to_status_id_str ?: 0;
            $tmp->text     = $tweet->retweeted_status->text;
          }

          $data[] = $tmp;
        }
      }

      return $data;
    }
  }

?>