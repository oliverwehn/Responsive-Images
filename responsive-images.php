<?php

/**
 * Responsive Images
 * by Oliver Wehn, www.oliverwehn.com
 * inspired by and partly based on Adaptive Images by Matt Wilcox, https://github.com/MattWilcox/Adaptive-Images
 *
 *
 * Configure values below
 *
 *
 * PATH_ROOT: Document Root
 * file system path
 */
define('PATH_ROOT', $_SERVER['DOCUMENT_ROOT']);
/**
 * PATH_CACHE: directory where resized image versions are stored
 * file system path
 */
define('PATH_CACHE', dirname(__FILE__) . '/sample/images_cache');
/**
 * PATH_PLACEHOLDER: placeholder image to be delivered before replacement
 * url
 */
define('URL_PLACEHOLDER', dirname($_SERVER['SCRIPT_NAME']) . '/sample/images/loading.gif');
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
 * JPEG quality
 * int value
 */
define('RES_JPEG_QUALITY', 80);
/**
 * How long should the browser store the cached image
 * int value
 */
define('BROWSER_CACHE', 60*60*24*7);
/**
 * Modify for debugging
 */
ini_set('memory_limit', '120M');
error_reporting('E_ALL');


/**
 * Modify below on your own risk
 */
$error = 0;
if(count($_GET) == 0 && $_COOKIE['responsive']) {
	// redirect to placeholder
	header('Location: ' . URL_PLACEHOLDER);
	die();
} else {
	// gather path information
	$url = parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
	$path = dirname($url) . '/' . basename($url);
	if(file_exists(PATH_ROOT . $path)) {
		// determine target width
		$max_width = RES_DEFAULT;
		$pxratio = isset($_GET['pxratio'])?$_GET['pxratio']:1;
		if((array_key_exists('pwidth', $_GET)) && ($_GET['pwidth'] > 0)) {
			$max_width = ceil(ceil($_GET['pwidth'] / RES_INTERVALS) * RES_INTERVALS * $pxratio);
		} elseif((array_key_exists('swidth', $_GET)) && ($_GET['swidth'] > 0)) {
			$max_width = ceil(ceil($_GET['swidth'] / RES_INTERVALS) * RES_INTERVALS * $pxratio);
		}
		$error = deliverImage($path, $max_width)?3:0;	
	} else {
		$error = 1;
	}
}

if($error > 0) {
	header("HTTP/1.0 404 Not Found");
}

/**
 * functions
 */
function deliverImage($path, $width) {
	if(!file_exists(PATH_ROOT . $path)) {
		echo "Image not found.";
		return false;
	}
	if(!is_numeric($width) || $width <= 0) {
		echo "No valid value for width provided.";
		return false;
	}
	
	if($filename = getImage($path, $width)) {
		sendImage($filename, BROWSER_CACHE);
	}
}

function getImage($src_path, $dst_width) {
	// check for cache dir
	$cache_dir = PATH_CACHE . dirname($src_path);
	if(!is_dir($cache_dir)) {
		// if not, create it
		if(!mkdir($cache_dir, 0777, true)) {
			return false;
		}
	}
	$cache_path = $cache_dir . '/' . preg_replace("#\.([a-z]+)$#i", "." . $dst_width . ".$1", basename($src_path));
	$root_path = PATH_ROOT;
	$src_path = ($root_path[strlen($root_path)-1] == '/'?substr($root_path, 0, -1):$root_path) . $src_path;

	if(!file_exists($cache_path)) {
		// Check the image dimensions
	 	$dimensions = getimagesize($src_path);
	  	$src_width = $dimensions[0];
	  	$src_height = $dimensions[1];
	  	
	  	// Get extension
	  	$extension = pathinfo($src_path, PATHINFO_EXTENSION);

		// return path of src image if smaller than max width
		if ($src_width <= $dst_width) { 
			return $src_path;
		}

		// resize image
		$ratio = $src_height / $src_width;
		$dst_height = ceil($dst_width * $ratio);
		$dst_image = imagecreatetruecolor($dst_width, $dst_height);
		// get original image
		switch ($extension) {
			case 'png':
				$src_image = @imagecreatefrompng($src_path);
			break;
			case 'gif':
				$src_image = @imagecreatefromgif($src_path);
			break;
			default: 
				$src_image = @imagecreatefromjpeg($src_path);
				imageinterlace($dst_image, true); 
			break;
		} 

		if($extension == 'png'){
			imagealphablending($dst_image, false);
			imagesavealpha($dst_image, true);
			$transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
			imagefilledrectangle($dst_image, 0, 0, $dst_width, $dst_height, $transparent);
		}
	  	
		imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height); // do the resize in memory
		imagedestroy($src_image);


		if(PROCESS_SHARPEN == TRUE && function_exists('imageconvolution')) {
			$int_sharpness = findSharp($width, $dst_width);
			$arr_matrix = array(
			  array(-1, -2, -1),
			  array(-2, $int_sharpness + 12, -2),
			  array(-1, -2, -1)
			);
			imageconvolution($dst_image, $arr_matrix, $int_sharpness, 0);
		}

		// save cache file
		switch ($extension) {
			case 'png':
			  $saved = imagepng($dst_image, $cache_path);
			break;
			case 'gif':
			  $saved = imagegif($dst_image, $cache_path);
			break;
			default:
			  $saved = imagejpeg($dst_image, $cache_path, RES_JPEG_QUALITY);
			break;
		}
		imagedestroy($dst_image);

		if (!$saved && !file_exists($cache_path)) {
			return false;
		}
	}
	return $cache_path;
}

// sharpen images function
// taken from Matt Wilcox’ adaptive-images.php
function findSharp($intOrig, $intFinal) {
	$intFinal = $intFinal * (750.0 / $intOrig);
	$intA     = 52;
	$intB     = -0.27810650887573124;
	$intC     = .00047337278106508946;
	$intRes   = $intA + $intB * $intFinal + $intC * $intFinal * $intFinal;
	return max(round($intRes), 0);
}

// helper function: Send headers and returns an image
// taken from Matt Wilcox’ adaptive-images.php
function sendImage($filename, $browser_cache) {
	$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	if (in_array($extension, array('png', 'gif', 'jpeg'))) {
		header("Content-Type: image/".$extension);
	} else {
		header("Content-Type: image/jpeg");
	}
	header("Cache-Control: private, max-age=".$browser_cache);
	header('Expires: '.gmdate('D, d M Y H:i:s', time()+$browser_cache).' GMT');
	header('Content-Length: '.filesize($filename));
	readfile($filename);
	exit();
}

?>