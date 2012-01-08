<?php
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 *
 * Six dollars on Sunup
 */
require '../app/WebApp.php';

$controller = SelectiveTweets_WebApp::factory();
$db = $controller->getDB();
$res = $db->query('SELECT COUNT(*) FROM selective_status_users;');
$row = $res->fetch();
var_dump($row);

