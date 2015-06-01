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
$fbuid = $controller->getFBUID();
$can_publish_stream = $controller->getCanPublishStream();

if (isset($_REQUEST['username'])) {
	$made_changes = $controller->saveProfile();
	$controller->redirect(ROOT_URL . '?made_changes=' . (int) $made_changes);
	exit();
}

$made_changes = !empty($_REQUEST['made_changes']);
$username = $controller->getTwitterName();


include '../templates/header.php';

if ($made_changes && $username) {
?>
<div class="alert alert-block alert-success">
     <h4 class="alert-heading"><i class="icon-thumbs-up"></i> That's it!</h4>
     <p>
	Any tweets you post that end with <strong>#fb</strong> should now update your Facebook status.
    </p>
</div>
<?php
}
?>

<table width="100%">
<tr>
<td width="50%" style="vertical-align: top;">

<form action="<?php echo ROOT_URL; ?>" method="post" class="require_fb_login" id="test2" style="padding: 15px; background-color: #fff; border: 4px solid #C6E2EE; height: 195px;">
	<h2>
		The one and only step:
	</h2>
	<p>
		<?php if ($fbuid) { ?>
		<a href="http://www.facebook.com/profile.php?id=<?php echo $fbuid; ?>"><img src="https://graph.facebook.com/<?php echo _h($fbuid); ?>/picture?type=square&amp;return_ssl_resources=1" width="50" height="50"  style="float: right; margin-left:10px;" /></a>
		<?php } ?>
		To configure for your profile page,<br />
		just enter your Twitter username:
	</p>
	<?php if ($username) {
		echo '<p>Currently configured to watch <strong>@' . _h($username) . '</strong> for updates</p>';
	}
	if ($username && !$can_publish_stream) {
		echo '<p style="color: red;"><strong>The app doesn\'t have permission to update your status</strong> <input type="submit" name="sub_save_permission" value="Fix this" class="btn btn-danger" /></p>';
	} ?>
	<div class="input-prepend">
	<span class="add-on">@</span><input type="text" name="username" value="<?php echo _h($username); ?>" class="input-medium" placeholder="your twitter ID">
	<input type="submit" name="sub_update_username" value="Save" class="btn btn-primary">
	<?php
	if ($username) {
		echo '<input type="submit" name="sub_clear" value="Clear" class="btn">';
	}
	?>
	</div>
	<p>
		If you have problems, see the <a href="<?php echo ROOT_URL; ?>help">help page</a>
	</p>
</form>

<ul>
	<li>Avoid confusing your Facebook friends</li>
	<li>Don't swamp your profile with too many updates</li>
	<li>Leave certain updates on Facebook for longer </li>
</ul>

</td>
<td width="50%" style="vertical-align: top;">
	<img src="<?php echo ROOT_URL; ?>img/app_3_115463795461_5640.gif" width="358" height="233" />
</td>
</tr>
</table>

<?php if ($username) { ?>
<p style="border: 1px solid #ffb646; background: #ffe6bf url(<?php echo ROOT_URL; ?>img/error.png) 10px 10px no-repeat; padding: 10px 10px 10px 40px;">
If you're currently using the <a href="http://apps.facebook.com/twitter" target="_top">Twitter application</a> or other app to update your status, remember to <a href="https://www.facebook.com/settings/?tab=applications" target="_top">remove or disable it</a> so it doesn't keep updating with all your tweets.
</p>
<?php } ?>

<script type="text/javascript">

$(document).ready(function() {
	var s = $('form.require_fb_login input:submit');
	for(i=0; i<s.length; i++) {
		b = $(s[i]);
		b.on('click', function(event) {
			event.preventDefault();
			FB.login(
				function(response) { b.closest('form')[0].submit(); },
				{scope: 'publish_actions,manage_pages,publish_pages', auth_type: 'rerequest'}
			);
		});
    }
});

</script>

<?php

include '../templates/footer.php';


