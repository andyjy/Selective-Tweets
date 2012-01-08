<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 *
 * Daemon to listen to Twitter's Streaming API for tweets continaing the hashtag #fb
 */
require dirname(dirname(__FILE__)) . '/app/CLIApp.php';
$controller = SelectiveTweets_CLIApp::factory();
$controller->trackStream();

