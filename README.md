Selective Tweets
================

Copyright 2009-2012 Andy Young andy@apexa.co.uk [@andyy](http://twitter.com/andyy)

License: MIT

[https://github.com/andyyoung/Selective-Tweets](https://github.com/andyyoung/Selective-Tweets)

This is the source code for Selective Tweets - an app that enables people that use both Facebook and Twitter to cross-post Tweets to their Facebook profile or page using the #fb hashtag.

The app can be found at: [http://apps.facebook.com/selectivetwitter/](http://apps.facebook.com/selectivetwitter/)

Source code released January 2012 under the MIT licence. You are free to use this code for educational purposes, create derivative works and redistribute so long as you retain the original licence and copyright notices acknowledging myself as the original author. Be innovative, don't be evil.

The Story
=========

I wrote up the story behind this app and how it collected 1M users in [this blog post](http://insomanic.me.uk/post/15507274276/selective-tweets-open-source).


Requirements
============

Pretty simple:

 - PHP under Linux
 - A web server (I use Apache)
 - MySQL database
 - Cron for scheduling jobs


How it works
============

The app provides a web interface based on the Facebook PHP SDK enabling users logged into Facebook to configure a Twitter account to watch for tweets. It then collects tweets containing the #fb hashtag using Twitter's streaming API and stores them in a database-based queue (MySQL). A daemon run every couple of minutes processes the queue of tweets, matches them against records of Facebook profiles/pages and compiles Facebook Graph API batch requests to cross-post updates as appropriate.


To get set up
=============

 - Edit config/config.php
 - Create DB according to config/database_schema.sql
 - Make www/ available under a webserver
 - Execute cron/check_stream_tracker.php to start tracking the twitter stream
 - Run the CLI scripts under cron/ on a regular basis to do the work

References
==========

 - [Twitter Streaming API](https://dev.twitter.com/docs/streaming-api)
 - [PHP Phirehose library](https://github.com/fennb/phirehose)
 - [Facebook Graph API](http://developers.facebook.com/)
 - [Facebook PHP SDK](https://github.com/facebook/facebook-php-sdk)


Have fun!

Andy Young

T: [@andyy](http://twitter.com/andyy)

E: andy@apexa.co.uk

B: [http://www.insomanic.me.uk](http://www.insomanic.me.uk)
