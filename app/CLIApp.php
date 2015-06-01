<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 */
$app_dir = dirname(__FILE__) . '/';
require_once $app_dir . 'BaseApp.php';
require_once $app_dir . 'TweetQueue.php';
require_once $app_dir . 'FilterTrack.php';
require_once $app_dir . '../lib/Process.php';

use Facebook\FacebookSession;
use Facebook\FacebookRequest;

/**
 * Controller for CLI scripts for the Selective Tweets app
 * Workhorse - handles collecting tweets and sending to FB
 */
class SelectiveTweets_CLIApp extends SelectiveTweets_BaseApp
{
	/**
	 * Let's get started
	 */
	protected function init()
	{
		// security - ensure we only run these scripts under CLI
		$this->requireCLI();

		parent::init();

		$this->fb = FacebookSession::newAppSession();
		try {
			$this->fb->validate();
		} catch (FacebookRequestException $ex) {
			// Session not valid, Graph API returned an exception with the reason.
			error_log($ex->getMessage());
		} catch (\Exception $ex) {
			// Graph API returned info, but it may mismatch the current app or have expired.
			error_log($ex->getMessage());
		}
	}	

	/**
	 * Ensures the current script is running under CLI, dies if not 
	 */
	protected function requireCLI()
	{
		if (php_sapi_name() != 'cli') {
			die("I don't think so..");
		}
	}

	/**
	 * The Grand Collecter of Tweets
	 *
	 * Uses FilterTrackConsumer based on Phirehose library to connect to the Twitter Streaming API
	 */
	public function trackStream()
	{
		$this->log('starting tracker', 'tracker');
		if (defined('LOG_DIR')) {
			ini_set('error_log', LOG_DIR . 'tracker.log');
		}
		$this->db->exec('set wait_timeout = 43200');
		$tweetQueue = new TweetQueue($this->db);
		$sc = new FilterTrackConsumer(TWITTER_OAUTH_TOKEN, TWITTER_OAUTH_SECRET, Phirehose::METHOD_FILTER);
		$sc->setTweetQueue($tweetQueue);
		$sc->setTrack(array('#fb'));
		$sc->consume();
		// we only reach here on error
		$this->log('exiting tracker', 'tracker');
	}

	/**
	 * Ensures the trackStream() daemon is (a) running and (b) not in a crashed state, restarts if not
	 */
	public function ensureTrackerRunning()
	{
		$tracker_script = dirname(dirname(__FILE__)) . '/cli/trackstream.php';
		if (Process::ensureRunning('php ' . $tracker_script, 'selectivetweets_streamtracker')) {
			$this->log('restarted tracker', 'tracker');
		} elseif($this->detectTrackerCrash() === self::CRASHED) {
			if (Process::ensureRunning('php ' . $tracker_script, 'selectivetweets_streamtracker', true)) {
				$this->log('restarted crashed tracker', 'tracker');
			}
		}
	}

	/**
	 * Restarts the tracker deamon
	 */
	public function restartTracker()
	{
		$tracker_script = dirname(dirname(__FILE__)) . '/cli/trackstream.php';
		if (Process::ensureRunning('php ' . $tracker_script, 'selectivetweets_streamtracker', true)) {
			$this->log('restarted tracker', 'tracker');
		}
	}

	/**
	 * Cleans out old, processed tweets from the DB queue
	 */
	public function cleanQueue()
	{
		$this->log('clearing queue', 'queue');
		$tweetQueue = new TweetQueue($this->db);
		$affected = $tweetQueue->clean();
		$this->log("cleaned $affected tweets from queue", 'queue');
	}

	/**
	 * Grab enqueued tweets and ping them across to Facebook
	 */
	public function processQueue()
	{
		$total_updates = 0;
		$this->log('processing queue', 'queue');
		$this->db->exec('set wait_timeout = 43200');
		$tweetQueue = new TweetQueue($this->db);
		while ($tweets = $tweetQueue->getTweets()) {
			$this->log('processing tweets', 'queue');
			$total_updates += $this->processTweets($tweets);
		}
		$this->log($total_updates . ' tweets processed', 'queue');
		$this->log('exiting', 'queue');
	}

	/**
	 * Process a bunch of tweets and send 'em to FB
	 */
	protected function processTweets($tweets)
	{
		$this->log('getting statii', 'queue');
		// $updates must be have 0-indexed integer keys with no gaps to match up with the correct results from the batch call
		$updates = array_values($this->processStatii($tweets));
		$total_updates = count($updates);
		$this->log('updates size: ' . count($updates), 'queue');
		reset($updates);
		while (current($updates)) {
			$i = 0;
			$results = array();
			$batch = array();
			$this_batch_updates = array();
			while ($i < 25 && current($updates)) {
				$i++;
				list($msg_final, $status, $row) = current($updates);
				$fbuid = $row['fbuid'];
				$user = $status['user'];
				$body = array(
					'message' => urlencode($msg_final),
				);
				if (!empty($status['link'])) {
					$body['link'] = urlencode($status['link']);
				}
				if ($row['fb_oauth_access_token']) {
					$body['access_token'] = urlencode($row['fb_oauth_access_token']);
				}
				if ($row['show_twitter_link'] && !$status['clear']) {
					$body['actions'] = json_encode(array(
						'name' => urlencode('follow @' . $user),
						'link' => urlencode('http://twitter.com/' . $user)
					));
				}
				$body = http_build_query($body);
				$body = urldecode($body);
				$request = array(
					'method' => 'POST',
					'body' => $body,
				);
				if ($row['fb_oauth_access_token']) {
					$request['relative_url'] = '/me/feed';
				} else {
					$request['relative_url'] = '/' . $fbuid . '/feed';
				}
				$batch[] = $request;
				$this_batch_updates[] = current($updates);
				next($updates);
			}
			$this->log('BATCH', 'queue');
			$this->log('batch: ' . count($batch), 'queue');

			try {
				$response = (new FacebookRequest($this->fb, 'POST', '?batch='.urlencode(json_encode($batch))))->execute();
				$results = $response->getGraphObject()->asArray();

				// process results
				$this->log('results: ' . count($results), 'queue');
				foreach ($results as $key => $result) {
					list($msg, $status, $row) = $this_batch_updates[$key];
					$fbuid = $row['fbuid'];
					$user = $status['user'];
					$id = $status['id'];
					$timestamp = $this->getStatusTimestamp($status);
					$now = date('D, j M H:i:s');
					if (!empty($result->code) && $result->code == 200) {
						// HTTP 200 - success!
						$this->db->exec("update tweet_queue set sent = 1 where id = " . $this->db->quote($id));
						$this->log("update tweet_queue set sent = 1 where id = " . $this->db->quote($id), 'queue');
						$this->db->exec("update selective_status_users set updated = " . $this->db->quote($timestamp) . ", exception_count = 0 where twitterid = " . $this->db->quote($user) . " and fbuid = " . $this->db->quote($fbuid) . " limit 1;");
						$this->log("update selective_status_users set updated = " . $this->db->quote($timestamp) . ", exception_count = 0 where twitterid = " . $this->db->quote($user) . " and fbuid = " . $this->db->quote($fbuid) . " limit 1;", 'queue');
						$this->log(': ' . $timestamp . ' ' . $user . ' - ' . $fbuid . ': ' . $msg, 'queue');
					} else {
						$this->db->exec("update tweet_queue set sent = 3 where id = " . $this->db->quote($id));
						$this->log("update tweet_queue set sent = 3 where id = " . $this->db->quote($id), 'queue');
						if ($timestamp > max($row['last_update_attempt'], $row['updated'])) {
							$this->db->exec("UPDATE selective_status_users SET last_update_attempt = " . $this->db->quote($timestamp) . ", exception_count = 0 WHERE twitterid = " . $this->db->quote($user) . " AND fbuid = " . $this->db->quote($fbuid) . " LIMIT 1");
						}
						$this->log(': ' . $timestamp .  ' ERROR ' . $result->code . ' - ' . $result->body . ' / ' . $user . ' - ' . $fbuid . ': ' . $msg, 'queue');
					}
				}
				$this->log('batch done', 'queue');
			} catch (Exception $e) {
				trigger_error('EXCEPTION ' . $e->getCode() . ': ' . $e->getMessage());
				// no knowledge these updates went through, but if this ever happens need to ensure we don't
				// get stuck in a loop constantly updating the same users with the same statii every 2 minutes..!
				// increment exception_count, we'll only do this so many times
				foreach ($results as $key => $result) {
					list($msg, $status, $row) = $this_batch_updates[$key];
					$fbuid = $row['fbuid'];
					$user = $status['user'];
					$id = $status['id'];
					$timestamp = $this->getStatusTimestamp($status);
					$now = date('D, j M H:i:s');
					$this->db->exec("UPDATE selective_status_users SET exception_count = exception_count + 1, last_update_attempt = " . $this->db->quote($timestamp) . " WHERE twitterid = " . $this->db->quote($user) . " AND fbuid = " . $this->db->quote($fbuid) . " LIMIT 1");
					$this->db->exec("UPDATE tweet_queue SET exception_count = exception_count + 1 WHERE id = " . $this->db->quote($id));
					$this->log(': ' . $timestamp . ' ERROR - incrementing exception count: ' . $user . ' - ' . $fbuid . ': ' . $msg, 'queue');
				}
			}
		}
		return $total_updates;
	}

	/**
	 * prepare each call to update status for a twitterid/fbuid combo
	 */
	public function processStatii($result)
	{
		$long_time_ago = time() - 12*60*60; // 12 hours ago - ignore tweets this old!
		$updates = array();
		while ($status = $result->fetch()) {
			// process status text
			$this->processStatus($status);
			$timestamp = $this->getStatusTimestamp($status);
			$status['clear'] = !(bool) trim($status['msg']);
			$atLeastOneUpdate = false;
			if ($status['user']) {
				$rs = $this->db->query("SELECT * FROM selective_status_users WHERE twitterid = " . $this->db->quote($status['user']) . " limit 50");
				$i = 0;
				while ($row = $rs->fetch()) {
					$i++;
					if ($i == 45) {
						trigger_error('warning: too many user matches found for twitter user: ' . $status['user']);
					}
					if (!$row['fbuid'] || !$row['twitterid']) {
						continue;
					}
					if ($row['updated'] >= $timestamp) {
						// already updated this fbuid with this tweet or a more recent one
						continue;
					} elseif ( false && ($status['exception_count'] > 2) ) {
						// had too many exceptions.. best skip in case we've actually updated this but dont know it..!
						$this->log('TOO MANY EXCEPTIONS (' . $status['exception_count'] . '): skipping.. ' . $status['user'] . ' ' . $status['created_at'] . ', ' . $status['text'], true, 'too_many_exceptions', 'queue');
						continue;
					} elseif ($timestamp < $long_time_ago) {
						// tweet many hours old - Twitter search was borked, don't bother no more!
						$this->log('MANY HOURS OLD(!) skipping.. ' . $status['user'] . ' ' . $status['created_at'] . ', ' . $status['text'], 'queue');
						continue;
					}
					if ($status['tag_anywhere'] && !$row['allow_tag_anywhere']) {
						continue;
					}
					$msg = $status['msg'];
					// fb user-specific customisations
					// name replacement 
					if ($row['replace_names']) {
						if (!empty($status['entities']['user_mentions']) && is_array($status['entities']['user_mentions'])) {
							foreach ($status['entities']['user_mentions'] as $mention) {
								if (!empty($mention['name']) && !empty($mention['screen_name'])) {
//								if (!empty($mention['name']) && !empty($mention['indices']) && is_array($mention['indices']) && count($mention['indices']) == 2) {
									$msg = str_ireplace('@' . $mention['screen_name'], ' ' . str_replace('@', '@%^&', $mention['name']) . ' (@%^&' . $mention['screen_name'] . ')', $msg);
								}
							}
							$msg = str_replace('@%^&', '@', $msg);
						}
					}
					// custom prefix
					if ($row['prefix']) {
						$msg = trim($row['prefix']) . ' ' . $msg;
					}
					// ok, we're good to go with this twitterid/fbuid combo
					$updates[$status['id'] . '-' . $row['fbuid']] = array($msg, $status, $row);
					$atLeastOneUpdate = true;
					$this->log('.', 'queue', false);
				}
				if (!$atLeastOneUpdate) {
					// we've not got anything to do for this tweet - flag it as finished with
					$this->db->exec("UPDATE tweet_queue SET sent = 5 WHERE id = " . $this->db->quote($status['id']));
				}
				$this->log(':', 'queue', false);
			} else {
				// invalid tweet with no user - flag it as finished with
				$this->db->exec("UPDATE tweet_queue SET sent = 4 WHERE id = " . $this->db->quote($status['id']));
			}
		}
		$this->log("\n", 'queue', false);
		return $updates;
	}

	/**
	 * (Pre-)process an individual tweet
	 *
	 * Cleans the tweet for display and analyses hashtags
	 *
 	 * @return  array(string, boolean)  The processed tweet, and whether it should only be posted if the user has allowed the hashtag anywhere in the tweet
	 */
	protected function processStatus(&$status)
	{
		$status['entities'] = json_decode($status['entities'], true);
		if (!is_array($status['entities'])) {
			// decode failed - invalid data
			$status['entities'] = array();
		}
		$msg = $status['text'];
		$msg = trim(html_entity_decode($msg));
		$msg = trim(preg_replace('/\s#fb\.(\s|$)/i', ' #fb ', $msg));
		$msg = trim(preg_replace('/#fb(\s+)(http:\/\/\S+)$/i', '$2 #fb', $msg));
		preg_match('/(.*)(\s((#(\S+)|(http:\/\/\S+))\s*)+)?$/isU', $msg, $matches);
		if (!empty($matches[1]) && !empty($matches[2])) {
			// we have a tweet and some hashtags/links on the end
			$matches[2] = preg_replace('/\s/', ' ', $matches[2]);
			// strip out known-meaningless tags (other services.. we know who came up with this idea first, eh.. ;)
			$matches[2] = str_ireplace(array(' #li ', ' #in ', ' #yam '), ' ', $matches[2] . ' ');
			// stick back together
			$msg = trim($matches[1] . $matches[2]);
		}
		$tag_anywhere = false;
		if (strtolower(substr($msg, -3)) != '#fb') {
			if (empty($matches[2]) || stripos($matches[2], '#fb') === false) {
				$tag_anywhere = true;
			}
			$msg = trim(str_ireplace('#fb', '', $msg));
		} else {
			$msg = trim(substr($msg, 0, strlen($msg) - 3));
		}
		// find link to feature
		$link = '';
		if (!empty($status['entities']['urls'][0]['expanded_url'])) {
			$link = $status['entities']['urls'][0]['expanded_url'];
		} elseif (!empty($status['entities']['urls'][0]['url'])) {
			$link = $status['entities']['urls'][0]['url'];
		}
		// replace t.co shortlinks with expanded ones
		if (!empty($status['entities']['urls'])) {
			foreach ($status['entities']['urls'] as $url) {
				if (!empty($url['expanded_url']) && !empty($url['url'])) {
					$msg = str_replace($url['url'], $url['expanded_url'], $msg);
				}
			}
		}
		$status['msg'] = $msg;
		$status['tag_anywhere'] = $tag_anywhere;
		$status['link'] = $link;
	}

	/**
	 * helper - get the creation time for a tweet as a unix timestamp
	 *
	 * @return  int  Unix timestamp
	 */
	protected function getStatusTimestamp($status)
	{
		$datetime = date_create($status['created_at']);
		return $datetime->format('U');
	}
}

