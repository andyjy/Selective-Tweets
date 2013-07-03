<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 */

// must remember not to disclose actual secrets/passwords on github..
require '/etc/selectivetweets.conf.php';

if (!defined('ROOT_URL')) {
	define('ROOT_URL', '/');
}

/*

the config file should define the following constants:

define('DB_HOST', 'hostname');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_NAME', 'database_name');

define('FB_APP_ID', 'app_id');
define('FB_APP_SECRET', 'secret');

define('ROOT_URL', '/'); // absolute or relative URL for links to where the app is hosted

// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "");
define("TWITTER_CONSUMER_SECRET", "");

// The OAuth data for the twitter account
define("OAUTH_TOKEN", "");
define("OAUTH_SECRET", "");

define('LOG_DIR', '/path/to/logs/');

*/

