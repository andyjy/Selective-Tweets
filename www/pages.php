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
	$controller->redirectToLogin('pages');
}

$pages = $controller->getUserPages();

include '../templates/header.php';

?>

<p>You can only update the status for pages you are an admin for.</p>

<?php

if ($controller->savePages($pages)) {
?>
<div class="alert alert-success">
	<strong>Success!</strong> Your changes have been saved.
	Any tweets you post that end with <strong>#fb</strong> should now update the status for your page(s).
</div>
<?php
}

if ($pages) {
	$pages_count = count($pages);
	if ($pages_count) {
		?>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
		<div class="alert alert-block alert-info">
		<?php
		if ($pages_count > 5) {
			?>
			<h4 class="alert-heading">
				Hey there, Social Media pro - what a lot of pages you have!
			</h4>
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
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="4817248">
  			<p style="text-align: center;"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" width="92" height="26" name="submit" alt="Make a donation using PayPal" style="vertical-align: middle; width:92px;"></p>
		</div>
		</form>
	<?php
	} // pages_count
	?>
	<form action="<?php echo ROOT_URL; ?>pages.php" method="post">
		<?php
		echo '<table style="width: 100%;" class="table table-striped"><tr><th colspan="2">Page</th><th>Twitter username</th>';
		foreach ($pages as $page) {
			echo '<tr>';
			echo '<td>';
			echo '<img src="https://graph.facebook.com/' . _h($page->id) . '/picture?type=square&amp;return_ssl_resources=1" width="50" height="50"/>';
			echo '</td>';
			echo '<td><a href="http://www.facebook.com/pages/blah/' . _h($page->id) . '">' .  _h($page->name) . '</a></td>';
			echo '<td><div class="input-prepend"><span class="add-on">@</span><input type="text" name="username' . _h($page->id) . '" value="' . _h($page->twitterid) . '"></div></td>';
			echo '</tr>';
		}
		echo '</table>';
		?>
		<p style="text-align: center;">
			<input type="submit" name="sub_update_username" value="Save Changes" class="btn btn-primary btn-large" />
		</p>
	</form>
<?php

} else {
	echo '<p>You are not the admin for any pages :(</p>';
}

include '../templates/footer.php';

