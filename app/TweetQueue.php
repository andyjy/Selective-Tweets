<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 */
/**
 * Model class providing a simple interface for our queue of tweets to process
 */
class TweetQueue
{
	const SOURCE_TRACK = 'track';
	const SOURCE_SEARCH = 'search';

	protected $db;
	protected $processGuid;
	protected $initialGetTimestamp;

	/**
	 * Set up the pieces
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->processGuid = getmypid();
	}

	/**
	 * Push a newly received tweet onto the end of the queue
	 */
	public function enqueueTweet($tweet_id, $created_at, $user, $text, $entities, $source)
	{
		return $this->db->exec('INSERT INTO tweet_queue(tweet_id, created_at, user, text, entities, from_' . ($source == self::SOURCE_TRACK ? 'track' : 'search') . ') values (' . $this->db->quote($tweet_id) . "," . $this->db->quote($created_at) . "," . $this->db->quote($user) . "," . $this->db->quote($text) . "," . $this->db->quote($entities) . ", 1) ON DUPLICATE KEY UPDATE from_" . ($source == self::SOURCE_TRACK ? 'track' : 'search') . " = 1");
	}

	/**
	 * Get a bunch of nice fresh tweets to process
	 */
	public function getTweets($count = 100)
	{
		if (!$this->initialGetTimestamp) {
			$this->initialGetTimestamp = time();
			// clean up crashed processes once per process
			$affectedRows = $this->db->exec('UPDATE tweet_queue SET processed_guid = 0 WHERE processed_guid > 0 and processed < ' . $this->db->quote($this->initialGetTimestamp - 5*60));
			if ($affectedRows) {
				trigger_error('reset processed_guid for ' . $affectedRows . ' rows');
			}
		} else {
			// release existing tweets for this process
			$this->db->exec('UPDATE tweet_queue SET processed_guid = 0 WHERE processed_guid = ' . $this->db->quote($this->processGuid));
		}
		// find tweets that have not been processed since we started this process and flag them for us
		$affectedRows = $this->db->exec('UPDATE tweet_queue SET processed_guid = ' . $this->db->quote($this->processGuid) . ', processed = ' . $this->db->quote($this->initialGetTimestamp) . ' WHERE processed_guid = 0 AND processed < ' . $this->db->quote($this->initialGetTimestamp) . ' AND sent = 0 ORDER BY id asc LIMIT ' . $count);
		if ($affectedRows) {
			return $this->db->query('SELECT * FROM tweet_queue WHERE processed_guid = ' . $this->db->quote($this->processGuid));
		} else {
			return null;
		}
	}

	/**
	 * Clean old tweets from the queue
	 */
	public function clean()
	{
		$result = $this->db->query('select id from tweet_queue where created_at < date_sub(now(), interval 1 hour) order by sent = 0 desc, id desc limit 1');
		if ($result && ($row = $result->fetch()) && (is_array($row))) {
			$last_id = $row['id'];
			if ($this->db->exec('insert ignore into tweet_queue2 select * from tweet_queue where id < ' . $last_id) || true) {
				return $this->db->exec('delete from tweet_queue where id < ' . $last_id);
			}
		}
	}
}

