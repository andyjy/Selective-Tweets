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

$fbuid = $controller->getFBUID();
$can_publish_stream = $controller->getCanPublishStream();

if (isset($_POST['sub_save_permission']) && !$can_publish_stream) {
	// likely IE7 bug
	$url = 'http://www.facebook.com/authorize.php?api_key=f1e3ea0bc8a86eb8dc9cb6b3d439dacd&v=1.0&ext_perm=publish_stream&next=http://apps.facebook.com/selectivetwitter/&next_cancel=http://apps.facebook.com/selectivetwitter/';
	$controller->redirect($url);
}

include '../templates/header.php';

if ($controller->saveSettings()) {
	echo '<fb:success>
     <fb:message>Your changes have been saved.</fb:message>
</fb:success>';
}

$user_data = $controller->getUserData();

?>

<form action="http://apps.facebook.com/selectivetwitter/settings.php" method="post" promptpermission="publish_stream" style="padding: 15px; background-color: #fff; border: 4px solid #C6E2EE;">
	<input type="hidden" name="sub_save" value="1"/>
	<h2>
		Settings
	</h2>
	<p>
		These settings currently only apply to your personal profile, not your pages.
		<?php if (!empty($user_data['twitterid'])) {
			echo '<br/>Currently configured to watch <strong>@' . _h($user_data['twitterid']) . '</strong> for updates';
		} ?>
	</p>
	<?php
	if (!empty($user_data['twitterid']) && !empty($user_data['can_publish_stream'])) {
		echo '<p style="color: red;"><strong>The app doesn\'t have permission to update your status</strong> <input type="submit" name="sub_save_permission" value="Fix this"></p>';
	} ?>
	<p>
		<input type="checkbox" style="vertical-align: middle;" id="allow_tag_anywhere" name="allow_tag_anywhere" value="1" <?php if (!empty($user_data['allow_tag_anywhere'])) echo 'checked="checked"'; ?>/><label for="allow_tag_anywhere"> Allow the #fb tag anywhere in the tweet?</label><br>(usually it has to be at the end to help you avoid updating your status by accident - turn this on to support twitterfeed etc)</label>
	</p>

	<p>
		If you would link to include a prefix before each tweet when you post it on Facebook, enter it here:<br>
	</p>
	<p>
		<label for="prefix">Prefix:</label>
		<input style="vertical-align: middle;" type="text" name="prefix" id="prefix" value="<?php echo _h(!empty($user_data['prefix']) ? $user_data['prefix'] : ''); ?>"/>
		&nbsp; e.g. "tweets:", "posted on Twitter:", "says"..
	</p>
	<p>
		<input type="checkbox" style="vertical-align: middle;" id="show_twitter_link" name="show_twitter_link" value="1" <?php if (!empty($user_data['show_twitter_link'])) echo 'checked="checked"'; ?>/><label for="show_twitter_link"> Include a link to your twitter profile below each update?</label>
	</p>
	<p>
		<input type="checkbox" style="vertical-align: middle;" id="replace_names" name="replace_names" value="1" <?php if (!empty($user_data['replace_names'])) echo 'checked="checked"'; ?>/><label for="replace_names"> Insert real names for each @mention</label>
	</p>
	<p>
		<input type="submit" name="sub_update_username" value="Save Changes"/>
	</p>
</form>

<p>
	<img src="https://sts.insomanic.me.uk/selectivestatus/img/error.png" width="16" height="16" alt="" style="margin-right: 5px; vertical-align: middle;">
	If you're currently using the <a href="http://apps.facebook.com/twitter">Twitter application</a> or other app to update your status, remember to <a href="http://www.facebook.com/editapps.php">remove or disable it</a> so that it doesn't keep updating with all your tweets.
</p>

<p style="margin-top: 20px;">
	Follow me: <a href="http://twitter.com/andyy">@andyy</a>
</p>

<?php include '../templates/footer.php'; ?>
