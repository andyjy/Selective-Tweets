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
	$url = 'https://www.facebook.com/authorize.php?api_key=f1e3ea0bc8a86eb8dc9cb6b3d439dacd&v=1.0&ext_perm=publish_stream&next=' . ROOT_URL . '&next_cancel=https://apps.facebook.com/selectivetwitter/';
	$controller->redirect($url);
}

include '../templates/header.php';

if ($controller->saveSettings()) {
?>
<div class="alert alert-success">
	<strong>Success!</strong> Your changes have been saved.
</div>
<?php
}

$user_data = $controller->getUserData();

?>

<form action="<?php echo ROOT_URL; ?>settings" method="post" promptpermission="publish_stream">
	<input type="hidden" name="sub_save" value="1"/>
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
		<label class="checkbox">
		<input type="checkbox" id="allow_tag_anywhere" name="allow_tag_anywhere" value="1" <?php if (!empty($user_data['allow_tag_anywhere'])) echo 'checked="checked"'; ?> />
		Allow the #fb tag anywhere in the tweet?<br />
		(usually it has to be at the end to help you avoid updating your status by accident - turn this on to support twitterfeed etc)
		</label>
	</p>

	<p>
		If you would link to include a prefix before each tweet when you post it on Facebook, enter it here:<br>
	</p>
	<p>
		<label for="prefix">Prefix:
		<input type="text" name="prefix" id="prefix" value="<?php echo _h(!empty($user_data['prefix']) ? $user_data['prefix'] : ''); ?>"/>
		&nbsp; e.g. "tweets:", "posted on Twitter:", "says"..
		</label>
	</p>
	<p>
		<label class="checkbox">
		<input type="checkbox" id="show_twitter_link" name="show_twitter_link" value="1" <?php if (!empty($user_data['show_twitter_link'])) echo 'checked="checked"'; ?>/>
		Include a link to your twitter profile below each update?</label>
	</p>
	<p>
		<label class="checkbox">
		<input type="checkbox" id="replace_names" name="replace_names" value="1" <?php if (!empty($user_data['replace_names'])) echo 'checked="checked"'; ?>/>
		Insert real names for each @mention</label>
	</p>
	<p>
		<input type="submit" name="sub_update_username" value="Save Changes" class="btn btn-primary btn-large" />
	</p>
</form>

<p style="border: 1px solid #ffb646; background: #ffe6bf url(<?php echo ROOT_URL; ?>img/error.png) 10px 10px no-repeat; padding: 10px 10px 10px 40px;">
If you're currently using the <a href="http://apps.facebook.com/twitter" target="_top">Twitter application</a> or other app to update your status, remember to <a href="https://www.facebook.com/settings/?tab=applications" target="_top">remove or disable it</a> so it doesn't keep updating with all your tweets.
</p>

<?php include '../templates/footer.php'; ?>
