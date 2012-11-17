twitstash
=========

Keeps a copy of a user's tweets in a MySQL database.

by [Scott Smitelli](mailto:scott@smitelli.com)

Installation and Requirements
-----------------------------

twitstash requires a PHP 5.3 installation with the following extensions
installed:

*   `curl` - Required by the `twitteroauth` library for making HTTP requests
*   `bcmath` - Deals with Twitter's 64-bit IDs represented as strings
*   `pdo` - Provides a database interface
*   `pdo-mysql` - PDO driver for MySQL

Additionally, a working MySQL installation will be needed. Any recent version
will work. (I personally had issues inserting emoji characters on MySQL 5.1.51,
but I didn't pursue it too far. If you encounter a similar problem, an easy hack
is to set the `text` column to `VARBINARY(140)` instead of `VARCHAR(140)`.)

###To install:

1.  `git clone --recursive https://github.com/smitelli/twitstash.git && cd twitstash`

2.  Ensure you have an empty database created for the stored tweets, and that
    there is a user account that can update this database.
    
3.  `mysql --user=YOUR_USER --password YOUR_DATABASE < twitstash.sql`

4.  `cp config.ini-sample config.ini`

5.  Edit `config.ini` to suit your fancy. You'll have to put your own Twitter
    and MySQL authentication in there.
    
6.  `chmod a+x twitstash.sh`

7.  `./twitstash.sh`

On the initial run, twitstash will incrementally download older and older tweets
until Twitter stops providing them. At the time of this writing, the most recent
3,200 tweets can be retrieved. After the initial run, twitstash will stop making
API requests once it encounters a tweet that it has previously stored.

The shell script is designed to never output anything, so you can add it in a
cron job without worrying about spamming root's inbox with junk. A file called
`debug.log` will be created (and appended) by the shell script. There's
generally nothing useful in that file.

Storage Format
--------------

Data collected by twitstash is kept in three tables. The following is a list of
columns and what types of data you can expect in each:

###tweets

*   `id` - Twitter's ID number for this tweet.
*   `created_at` - The date and time this tweet was created. This time is stored
    relative to the `timezone` setting in config.ini.
*   `text` - The raw text of this tweet. There may be t.co URLs in here -- the
    expanded URL is stored in the `urls.expanded_url` column.
*   `source` - The URL and name of the client that created this tweet, typically
    expressed as an HTML anchor tag.
*   `reply_id` - The ID number of the tweet that this tweet is replying to. 0 if
    this tweet is not a reply.
*   `rt_id` - The ID number of the tweet that was retweeted. 0 if this tweet is
    not a retweet.
*   `place_id` - A 16-digit hex string identifying the "place" associated with
    this tweet. JOINing this column on `places.id` will return a lot more info.
    Also see <https://dev.twitter.com/docs/api/1.1/get/geo/id/%3Aplace_id>
*   `latitude` - The exact point latitude reported by the client. North is
    positive, south is negative. 0 if this tweet had no geo data.
*   `longitude` - The exact point longitude reported by the client. East is
    positive, west is negative. 0 if this tweet had no geo data.
*   `touched` - Used internally to determine if any tweets have "disappeared"
    and should be marked as deleted. Always 0, unless the script died mid-run.
*   `deleted` - **Estimated** date/time a tweet was deleted. The more frequently
    twitstash is run, the more accurate this column becomes. NULL if this tweet
    was never deleted (or, at least, it was not deleted when we last checked).

###places

*   `id` - A 16-digit hex string identifying this place.
*   `place_type` - One of 'city', 'neighborhood', or 'admin'. There may be more.
*   `full_name` - The full text name of this place.
*   `country` - The full text name of the country that contains this place.
*   `centroid_lat` - Latitude, averaging all points of the bounding box.
*   `centroid_lon` - Longitude, averaging all points of the bounding box.

See `tweets.latitude` and `tweets.longitude` for info on the exact format of the
latitude/longitude pairs.

###urls

*   `url` - The shortened t.co link present in a tweet's text.
*   `expanded_url` - The URL that the shortened link points to.

Acknowledgements
----------------

This package includes Abraham Williams' `twitteroauth` library.
<https://github.com/abraham/twitteroauth>