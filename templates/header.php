<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://ogp.me/ns/fb#">
  <head>
    <meta charset="utf-8">

<link rel="stylesheet" type="text/css" href="<?php echo ROOT_URL; ?>css/bootstrap.min.css" />

<style type="text/css">
body, td { 
    font-size: 12px;
    color: #333;
    font-family: 'lucida grande', tahoma, verdana, arial;
} 
a { color: #3B5998; text-decoration: none; }
</style>

<meta property="og:title" content="Selective Tweets"/>
<meta property="og:image" content="http://graph.facebook.com/selectivetwitter/picture?type=large"/>

<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
    // This is more like it!
  });
</script>

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-7425094-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

</head>
<body>

<div id="fb-root"></div>

<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '<?php echo FB_APP_ID; ?>',
      cookie     : true,
      status     : true,
      xfbml      : true,
      version    : 'v2.3'
    });
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>

<h1 style="margin-top: 15px;">Welcome to Selective Tweets! (#fb)</h1>

<p style="font-size: 15px; line-height: 19px;">Selective Tweets lets you update your Facebook status from Twitter - BUT only when you want.
<br /><strong>Just end a tweet with the #fb hashtag</strong> when you want it to post to Facebook - simple!</p>

<?php include 'tabs.php'; ?>

