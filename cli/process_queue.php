<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 *
 * Reads tweets from the queue and posts to Facebook
 * Continues until no tweets left in the queue and then terminates
 * Run every minute or so, e.g. from cron
 */
require dirname(dirname(__FILE__)) . '/app/CLIApp.php';
$controller = SelectiveTweets_CLIApp::factory();
$controller->processQueue();

