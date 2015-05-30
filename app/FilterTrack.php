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
 * Phirehose library consumer class to receive a stream of tweets and stick them into our TweetQueue
 */
class FilterTrackConsumer extends OauthPhirehose
{
	protected $connectFailuresMax = 200;
	protected $tweetQueue;

	/**
	 * #construction
	 */
	public function setTweetQueue($tweetQueue)
	{
		$this->tweetQueue = $tweetQueue;
	}

	/**
	 * Enqueue each status
	 *
	 * @param string $status
	 */
	public function enqueueStatus($status)
	{
		$data = json_decode($status, true);
		if (is_array($data)) {
			if (isset($data['user']['screen_name'])) {
				if (empty($data['retweeted_status'])) {
					$user = $data['user']['screen_name'];
					$text = $data['text'];
					$created = date('Y-m-d H:i:s', strtotime($data['created_at']));
					$id = $data['id'];
					$entities = json_encode($data['entities']);
					$this->tweetQueue->enqueueTweet($id, $created, $user, $text, $entities, TweetQueue::SOURCE_TRACK);
					echo '.';
				} else {
					echo 'R';
				}
			} elseif (isset($data['limit'])) {
				trigger_error('received LIMIT: ' . (isset($data['limit']['track']) ? $data['limit']['track'] : ''));
			} else {
				trigger_error('UNKNOWN: ' . print_r($data, true));
			}
		} else {
			trigger_error('json decode error: ' . $status);
		}
	}
}

