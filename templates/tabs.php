<?php

/*if ($fbuid) {

	connect_db();

	$rs = $_db->query("SELECT last_timestamp from sts_delay ORDER BY id desc limit 1");
	if ($row = $rs->fetch()) {
		$delay = time() - $row['last_timestamp'];
		$delay = $delay / 60;
		
		$h = (int) ($delay / 60);
		$m = $delay % 60;

		if ($delay <= 4) {
			echo '<p>Current time to update: <strong>less than 2 minutes</strong> - everything is working normally :)</p>';
		} elseif ($delay < 15) {
			echo '<p>Current time to update: <strong>' . ($h ? ($h . ' hour' . ($h > 1 ? 's ' : ' ')) : '') . $m . ' minutes</strong>. Twitter is under heavy load right now. :(<br>Usually the delay is under 2 minutes - We\'ll sync your tweets as soon as Twitter gives them to us!</p>';
		} else {
			echo '<p>Current time to update: <strong>' . ($h ? ($h . ' hour' . ($h > 1 ? 's ' : ' ')) : '') . $m . ' minutes</strong>. Sorry, Twitter is having problems at the moment :(<br>Usually the delay is under 2 minutes - We\'ll sync your tweets as soon as Twitter gives them to us!</p>';
		}
	}
}

<p style="padding: 10px; background-color: #ffebe8; border: 1px solid red;"><strong>Status Update Dec 29th 15:00 GMT: Normal service resumed</strong><br>
Facebook has now restored this app - your updates should post again and previous updates reappear on your profile.<br>
If you continue to experience problems please try reentering your twitter name below
<?php /* Facebook has temporarily prevented this app from posting your updates. You may notice that previous updates have also been hidden. I've contacted Facebook and they should restore this app within the next 24 hours - thanks for your patience.<br>In the meantime you can still sign up or edit your settings.<br>
<a href="http://www.facebook.com/selectivetwitter?sk=wall">More info on the app page</a> &middot; Follow <a href="http://twitter.com/andyy">@andyy</a> for updates* / ?>
</p>

<?php 
*/

$qs = '';
if (!empty($_REQUEST['fb_page_id'])) {
	$qs = '?fb_page_id=' . htmlspecialchars(urlencode($_REQUEST['fb_page_id']));
}
?>
<fb:tabs>
  <fb:tab-item href='http://apps.facebook.com/selectivetwitter/<?php echo $qs; ?>' title='Your Profile' <?php if (!(strpos($_SERVER['REQUEST_URI'], 'settings') || strpos($_SERVER['REQUEST_URI'], 'pages') || strpos($_SERVER['REQUEST_URI'], 'help'))) echo "selected='true' "; ?>/>
  <fb:tab-item href='http://apps.facebook.com/selectivetwitter/pages<?php echo $qs; ?>' title='Your Fan Pages' <?php if (strpos($_SERVER['REQUEST_URI'], 'pages')) echo "selected='true' "; ?>/>
  <fb:tab-item href='http://apps.facebook.com/selectivetwitter/settings<?php echo $qs; ?>' title='Settings' <?php if (strpos($_SERVER['REQUEST_URI'], 'settings')) echo "selected='true' "; ?>/>
  <fb:tab-item href='http://apps.facebook.com/selectivetwitter/help<?php echo $qs; ?>' title='Help' <?php if (strpos($_SERVER['REQUEST_URI'], 'help')) echo "selected='true' "; ?>/>
 </fb:tabs>
