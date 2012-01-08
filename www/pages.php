<?php 
/**
 * This file is part of Selective Tweets.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Andy Young <andy@apexa.co.uk>
 * @license    MIT
 */
require '../app/WebApp.php';

$controller = SelectiveTweets_WebApp::factory();
$controller->requireLogin();
$fb = $controller->getFacebook();

if (!$controller->getCanPostToPages()) {
	$url = $fb->getLoginURL(array('redirect_uri' => 'http://apps.facebook.com/selectivetwitter/pages', 'scope' => 'publish_stream,manage_pages,offline_access'));
	$controller->redirect($url);
}

$pages = $controller->getUserPages();

include '../templates/header.php';

?>

<div style="padding: 15px; background-color: #fff; border: 4px solid #C6E2EE;">  

<h2>Your Pages</h2>
<p>You can only update the status for pages you are an admin for.</p>

<?php

if ($controller->savePages($pages)) {
	echo '<fb:success>
     <fb:message>Changes saved.</fb:message>
     Any tweets you post that end with <strong>#fb</strong> should now update the status for your page(s).
</fb:success>';
}

if ($pages) {
	$pages_count = count($pages);
	if ($pages_count) {
		if ($pages_count > 5) {
			?>
			<h2>
				Hey there, Social Media pro - what a lot of pages you have!
			</h2>
			<p>
				This app was built by me - <a href="http://twitter.com/andyy">Andy</a>. I hope you find it useful! If it helps you, your company, brand, clients or causes, please consider making a donation.. :)
			</p>
			<?php
		} else {
			?>
			<p>
				This app was built by me - <a href="http://twitter.com/andyy">Andy</a>. I hope you find it useful! It's been growing quite popular, so if it helps you, your company, brand or cause, you may like to consider making a small donation.. :)
			</p>
			<?php
		}
		?>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="margin-bottom: 1em;">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="4817248">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Make a donation using PayPal" style="vertical-align: middle;">
			<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
		</form>
	<?php
	} // pages_count
	?>
	<form action="http://apps.facebook.com/selectivetwitter/pages.php" method="post">
		<?php
		echo '<table style="width: 100%;"><tr><th colspan="2">Page</th><th>Twitter username</th>';
		foreach ($pages as $page) {
			echo '<tr>';
			echo '<td>';
			echo '<img src="https://graph.facebook.com/' . _h($page['id']) . '/picture?type=square&amp;return_ssl_resources=1" width="50" height="50"/>';
			echo '</td>';
			echo '<td><a href="http://www.facebook.com/pages/blah/' . _h($page['id']) . '">' .  _h($page['name']) . '</a></td>';
			echo '<td><strong>@</strong>&nbsp;<input type="text" name="username' . _h($page['id']) . '" value="' . _h($page['twitterid']) . '"></td>';
			echo '</tr>';
		}
		echo '</table>';
		?>
		<p style="text-align: center;">
			<input type="submit" name="sub_update_username" value="Save Changes">
		</p>
	</form>
<?php

} else {
	echo '<p>You are not the admin for any pages.</p>';
}

?>

</div>

<p style="margin-top: 20px;">
Follow me: <a href="http://twitter.com/andyy">@andyy</a>
</p>

<?php

include '../templates/footer.php';

