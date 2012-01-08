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
$username = _h($controller->getTwitterName());

include '../templates/header.php';

?>

<h3 style="margin-top: 1em;">If all your updates are going to Facebook (not just those tagged with #fb):</h3>

<p>
This app only sends updates to Facebook if they're tagged with #fb. <br/>
If other updates are appearing, you must have another app installed that is also sending updates to Facebook - most likely the <a href="http://apps.facebook.com/twitter">'official' Twitter application</a>.<br/>
<strong>To fix this you need to go to your <a href="http://www.facebook.com/editapps.php">list of installed Facebook applications</a> and remove or disable all other twitter apps</strong> so that they don't keep updating with all your tweets.
</p>

<h3>If none of your updates are appearing:</h3>

<p>Follow these steps to solve any problems with the app not working:</p>

<ol>

<li><strong>Are your updates protected?</strong><br/>
This app won't work if your updates are protected. Go to <a href="http://twitter.com/account/settings">http://twitter.com/account/settings</a> and un-tick the box "protect my updates".</li>

<li style="margin-top: 0.5em;"><strong>Are your updates appearing in Twitter Search?</strong> <strong style="color: red;">(This is usually the problem if the app worked before but suddenly stopped)</strong><br/>
Sometimes there is a problem with Twitter accounts where your updates don't appear in the public search results, even though your updates are not protected. To check go to <a href="http://search.twitter.com/search?q=<?php echo $username; ?>">http://search.twitter.com/search?q=<?php echo $username; ?></a> to do a search for your twitter username and ensure that your updates appear. <br/>
<strong style="color: red;">If your updates don't appear in the search,</strong> <strong>try protecting your updates and then unprotecting them again</strong> by ticking the box at <a href="http://twitter.com/account/settings">http://twitter.com/account/settings</a>, saving, and then going back and un-ticking it. <strong>You'll need to post a new tweet to test whether this worked</strong>.<br>
<strong style="color: red;">If you've tried this and your tweets still don't appear in the search</strong>, you need to <a href="http://help.twitter.com/"><strong>contact Twitter support</strong></a> and ask them to fix your account so that your tweets are public and searchable. <br><strong>Unfortunately I cannot do anything to fix this for you if this is the problem</strong></li> 

<li style="margin-top: 0.5em;"><strong>Are you waiting long enough for your update to go through?</strong><br/>
Usually your updates will appear on Facebook within 2 minutes, however sometimes when Twitter is having difficulties (fail whale..) it can take quite a bit longer. To check whether your last update should have gone through, do a search for yourself on Twitter search at <a href="http://search.twitter.com/search?q=<?php echo $username; ?>">http://search.twitter.com/search?q=<?php echo $username; ?></a>. If your update is displaying then it should appear on Facebook within 2 minutes. If not then we need to wait for Twitter to catch up.</li>


<li style="margin-top: 0.5em;"><strong>Is the #fb hashtag at the end of your tweet?</strong><br/>
<ul>
<li>The hashtag should be added to the end of your tweet (one exception: it's ok if the #fb is followed by a space and then a link, e.g. &quot;<strong>This is my tweet #fb http://linky.com/linky</strong>&quot;. This ensures it will still work with apps that automatically add links e.g. TwitPic)</li>
<li>There must be a space before the hashtag</li>
</ul>
</li>

<li style="margin-top: 0.5em;">
<strong>Have you installed the app correctly?</strong><br/>
Go back to the setup page (<a href="http://apps.facebook.com/selectivetwitter/">http://apps.facebook.com/selectivetwitter/</a> for your personal profile, <a href="http://apps.facebook.com/selectivetwitter/pages.php">http://apps.facebook.com/selectivetwitter/pages.php</a> for fan pages) and check the following:
<ul>
<li>Have you entered your twitter username correctly?</li>
<li>Have you given the app permission to update your status on Facebook? (You'll see a message if not)</li>
</ul>

</li>

</ol>

<?php /*<h3>If the app isn't respecting your privacy settings for status updates:</h3>
<h3>If your updates don't appear (or appear as blank entries) on Facebook mobile (iPhone etc):</h3>
<h3>If your updates don't appear in the &quot;status updates&quot; filtered lists in the news feed and FB mobile apps:</h3>

<p>These three issues are unfortunate side-effects caused by the app setting to include a link to your Twitter profile below each update. Basically they're bugs with Facebook not working as it promised :(  If you added the app after 28th June this feature is turned off by default. If you added the app before June 28th and have these problems, you can solve them by un-ticking the box to include a link to your twitter profile on the <a href="http://apps.facebook.com/selectivetwitter/settings.php">settings page</a>.</p>
*/ ?>

<h3>If the app used to work for you but doesn't any more:</h3>

<p>The most common cause of this problem is Twitter search stops indexing your tweets. See point #2 under the &quot;if it doesn't work&quot; section above.</p>
<p>Other possible causes are if you changed your twitter username (in which case you have to set this app up again with your new name) or if you protected your updates (in which case this app can no longer work).</p>

<h3>If you've tried all the above and it's still not working:</h3>

<p><strong>Are you sure you've tried everything above? ;)</strong>  This app is used successfully by thousands of people each day and every problem I've ever heard about is listed above. </p>

<p>You can click the <strong>Contact</strong> link at the bottom of this page to send me a message. <strong>Include your twitter username and as much information as you can</strong> to ensure you get a reply a.s.a.p ;)<p>

<?php include '../templates/footer.php'; ?>
