<?php

/**
 * Responsive Images
 * by Oliver Wehn, www.oliverwehn.com
 * inspired by Adaptive Images by Matt Wilcox, https://github.com/MattWilcox/Adaptive-Images
 *
 *
 * Configure values below
 *
 *
 * PATH_CACHE: directory where resized image versions are stored
 * file system path
 */
define('PATH_CACHE', PATH_IMAGES . '_cache');
/**
 * PATH_PLACEHOLDER: placeholder image to be delivered before replacement
 * file system path
 */
define('PATH_PLACEHOLDER', PATH_IMAGES . '/loading.gif');
/**
 * pixel interval to determine width of image to be served (width = ceil(imagewidth / interval) * inteval)
 * the smaller the number, the more versions of each image are likely to be generated and cached
 * in number of pixels
 */
define('RES_INTERVALS', 100); // number of pixels
/**
 * max width of images to be served as fallback
 * in number of pixels
 */
define('RES_DEFAULT', 800); 

/**
 * Modify below on your own risk
 */
$error = false;

if(array_key_exists('u', $_GET)) {
	$path = dirname($_GET['u']);
	$filename = basename($_GET['u']);
}

if($error) {
	//header("HTTP/1.0 404 Not Found");
}
?>