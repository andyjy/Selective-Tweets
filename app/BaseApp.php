<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 */
$lib_dir = dirname(dirname(__FILE__)) . '/lib/';
require_once $lib_dir . 'error_handler.php';
require_once $lib_dir . '../config/config.php';
if (defined('LOG_DIR')) {
	ini_set('error_log', LOG_DIR . 'selectivestatus.log');
}
require_once $lib_dir . 'DB/MySQL.php';
require $lib_dir . '../vendor/autoload.php';

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

/**
 * Controller-esque stuff for the Selective Tweets app
 *
 * Extended by concrete controllers for the Wep App and CLI scripts
 */
abstract class SelectiveTweets_BaseApp
{
	const CRASHED = 0;
	const OK = 1;
	const TEST_FAILED = -1;

	/** @var DB  DB Connection **/
	protected $db;

	/** @var Facebook  FacebookSession **/
	protected $fb;

	/**
 	 * Factory
	 */
	public static function factory()
	{
		$instance = new static;
		$instance->init();
		return $instance;
	}

	/**
	 * Protected constructor to force use of factory
	 */
	protected function __construct() { }

	/**
	 * Let's get this party started
	 */
	protected function init()
	{
		$this->db = MySQL::factory(DB_HOST, DB_NAME, DB_USER, DB_PASS);
		FacebookSession::setDefaultApplication(FB_APP_ID, FB_APP_SECRET);
	}

	/**
	 * Accessor for the DB connection
	 *
	 * @return DB
	 */
	public function getDB()
	{
		return $this->db;
	}

	/**
	 * Detects whether the tracker for the Twitter feed is returning tweets a-ok
	 * 
	 * Returns self::OK if all's fine, other return values indicate a problem
	 *
 	 * @returns self::OK|self::CRASHED|self::TEST_FAILED
	 */
	public function detectTrackerCrash()
	{
		$rs = $this->db->query('SELECT enqueued < DATE_SUB(NOW(), INTERVAL 60 SECOND) AS last_enqueued_delay FROM tweet_queue ORDER BY id DESC LIMIT 1');
		if ($rs && ($row = $rs->fetch())) {
			if ($row['last_enqueued_delay']) {
				return self::CRASHED;
			} else {
				return self::OK;
			}
		} else {
			return self::TEST_FAILED;
		}
	}

	/**
	 * A logger's job is to log logs
	 */
	protected function log($msg, $file = 'log', $newline = true)
	{
		if ($newline) {
			$msg = date('D, j M H:i:s') . ' ' . $msg . "\n";
		}
		echo $msg;
		file_put_contents(LOG_DIR . $file, $msg, FILE_APPEND);
	}
}

