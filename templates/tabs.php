<?php
$qs = '';
if (!empty($_REQUEST['fb_page_id'])) {
	$qs = '?fb_page_id=' . htmlspecialchars(urlencode($_REQUEST['fb_page_id']));
}
?>

<ul class="nav nav-tabs">
  <li <?php if (!(strpos($_SERVER['REQUEST_URI'], 'settings') || strpos($_SERVER['REQUEST_URI'], 'pages') || strpos($_SERVER['REQUEST_URI'], 'help'))) echo 'class="active" '; ?>><a href="<?php echo ROOT_URL . $qs; ?>">Your Profile</a></li>
  <li <?php if (strpos($_SERVER['REQUEST_URI'], 'pages')) echo 'class="active" '; ?>><a href="<?php echo ROOT_URL; ?>pages<?php echo $qs; ?>" >Your Pages</a></li>
  <li <?php if (strpos($_SERVER['REQUEST_URI'], 'settings')) echo 'class="active" '; ?>><a href="<?php echo ROOT_URL; ?>settings<?php echo $qs; ?>">Settings</a></li>
  <li <?php if (strpos($_SERVER['REQUEST_URI'], 'help')) echo 'class="active" '; ?>><a href="<?php echo ROOT_URL; ?>help<?php echo $qs; ?>">Help</a></li>
</ul>

