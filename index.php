<?php
require_once('lib/LastImage.php');
$lastfm = LastImage::getInstance();
$lastfm->setApiKey("your last.fm api key");
$lastfm->setCacheDir("cache/");
$lastfm->setDefaultImage("cache/default.jpeg");
$artist_image = $lastfm->grab("Cher", "", "", "medium");
$track_image = $lastfm->grab("Cher", "Believe", "", "medium");
$album_image = $lastfm->grab("Cher", "", "Believe", "medium");
?>

<h1>LastImage</h1>

LastImage - PHP Wrapper for the Last.fm API for images. 

<h2>Artist Image</h2>

<ul>

<li><?php echo $artist_image; ?></li>

<li><img src="<?php echo $artist_image; ?>"></li>

</ul>

<h2>Track Image</h2>

<ul>

<li><?php echo $track_image; ?></li>

<li><img src="<?php echo $track_image; ?>"></li>

</ul>

<h2>Album Image</h2>

<ul>

<li><?php echo $album_image; ?></li>

<li><img src="<?php echo $album_image; ?>"></li>

</ul>
