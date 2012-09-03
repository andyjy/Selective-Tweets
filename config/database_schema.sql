CREATE TABLE `selective_status_users` (
  `fbuid` bigint(20) unsigned NOT NULL,
  `twitterid` char(15) NOT NULL,
  `updated` int(10) unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update_attempt` int(10) unsigned NOT NULL,
  `exception_count` int(11) NOT NULL DEFAULT '0',
  `is_page` tinyint(1) unsigned NOT NULL,
  `show_twitter_link` tinyint(1) NOT NULL DEFAULT '1',
  `prefix` char(25) NOT NULL,
  `allow_tag_anywhere` tinyint(1) NOT NULL DEFAULT '0',
  `replace_names` tinyint(1) NOT NULL DEFAULT '1',
  `fb_oauth_access_token` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`fbuid`),
  KEY `twitterid` (`twitterid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tweet_queue` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tweet_id` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `enqueued` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `from_track` tinyint(1) NOT NULL DEFAULT '0',
  `from_search` tinyint(1) NOT NULL DEFAULT '0',
  `user` varchar(15) NOT NULL,
  `text` text NOT NULL,
  `entities` text,
  `link` varchar(140) NOT NULL DEFAULT '',
  `processed` int(11) NOT NULL,
  `processed_guid` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `sent` tinyint(1) NOT NULL DEFAULT '0',
  `exception_count` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tweet_id` (`tweet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

