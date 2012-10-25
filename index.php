<?php
require_once('lib/LastImage.php');
$lastfm = LastImage::getInstance();
$lastfm->setApiKey("your last.fm api key");
$lastfm->setCacheDir("cache/");
$lastfm->setDefaultImage("cache/default.jpeg");
$i = $lastfm->grab("Cher", "", "", "medium");
?>

<h1>LastImage</h1>

LastImage - PHP Wrapper for the Last.fm API for images. 

<ul>

<li><?php echo $i; ?></li>

<li><img src="<?php echo $i; ?>"></li>

</ul>
