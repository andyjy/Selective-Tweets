<?php                                                                                                                                                                                                                      
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 *
 * HTTP-accessible script to alert when tracker crashes
 * For use with e.g. Pingdom monitoring service
 */                                                                                                                                                                                                                           
require '../app/WebApp.php';

$controller = SelectiveTweets_WebApp::factory();
$result = $controller->detectTrackerCrash();
if ($result === SelectiveTweets_BaseApp::CRASHED) {
	echo 'FAIL';
} elseif ($result === SelectiveTweets_BaseApp::OK) {
	echo 'OK';
} else {
	echo 'TEST_FAILED';
}
                                                                                                                                                                                                                           
