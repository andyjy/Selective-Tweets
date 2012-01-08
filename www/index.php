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
	if (!$fbuid || !$can_publish_stream) {
		// likely IE7 bug
		$url = 'http://www.facebook.com/authorize.php?api_key=f1e3ea0bc8a86eb8dc9cb6b3d439dacd&v=1.0&ext_perm=publish_stream&next=http://apps.facebook.com/selectivetwitter/&next_cancel=http://apps.facebook.com/selectivetwitter/';
		$controller->redirect($url);
	}
	$made_changes = $controller->saveProfile();
}

include '../templates/header.php';

if (!empty($made_changes) && !empty($_REQUEST['username'])) {
	echo '<div style="margin: 10px 0;"><fb:success>
	     <fb:message>That\'s it!</fb:message>
	     Any tweets you post that end with <strong>#fb</strong> should now update your Facebook status.'
	. '</fb:success></div>';
}

$username = $controller->getTwitterName();

?>

<table width="100%">
<tr>
<td width="50%" style="vertical-align: top;">

<form action="https://apps.facebook.com/selectivetwitter/" method="post" requirelogin="1" promptpermission="publish_stream" style="padding: 15px; background-color: #fff; border: 4px solid #C6E2EE;">
	<h2>
		The one and only step:
	</h2>
	<p>
		<fb:profile-pic uid="loggedinuser" size="square" linked="true" style="float: right; margin-left:10px; "/>
		To configure for 
		<?php if ($fbuid) { ?>
		<fb:name uid="loggedinuser" useyou="false" capitalize="true" />'s
		<?php } else { echo 'your'; } ?>
		 profile page,<br>
		just enter your Twitter username:
	</p>
	<?php if ($username) {
		echo '<p>Currently configured to watch <strong>@' . _h($username) . '</strong> for updates</p>';
	}
	if ($username && !$can_publish_stream) {
		echo '<p style="color: red;"><strong>The app doesn\'t have permission to update your status</strong> <input type="submit" name="sub_save_permission" value="Fix this"></p>';
	} ?>
	<strong>@</strong>&nbsp;<input type="text" name="username" value="<?php echo _h($username); ?>">
	<input type="submit" name="sub_update_username" value="Save">
	<?php if ($username) { echo '<input type="submit" name="sub_clear" value="Clear">'; } ?>
	<p>
		If you have problems, see the <a href="http://apps.facebook.com/selectivetwitter/help">help page</a>
	</p>
</form>

<ul>
	<li>Avoid confusing your Facebook friends</li>
	<li>Don't swamp your profile with too many updates</li>
	<li>Leave certain updates on Facebook for longer </li>
</ul>

<p>
	<img src="https://sts.insomanic.me.uk/selectivestatus/img/error.png" width="16" height="16" alt="" style="margin-right: 5px; vertical-align: middle;">
	If you're currently using the <a href="http://apps.facebook.com/twitter">Twitter application</a> or other app to update your status, remember to <a href="http://www.facebook.com/editapps.php">remove or disable it</a> so that it doesn't keep updating with all your tweets.
</p>

<p style="margin-top: 20px;">
Follow me: <a href="http://twitter.com/andyy">@andyy</a>
</p>

</td>
<td style="vertical-align: top;">
	<img src="https://sts.insomanic.me.uk/selectivestatus/img/app_3_115463795461_5640.gif">
</td>
</tr>
</table>

<?php

include '../templates/footer.php';


