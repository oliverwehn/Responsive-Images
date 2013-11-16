<?php
/**
 * Responsive Images
 * V. 0.8
 * 11/16/2013
 * by Oliver Wehn, www.oliverwehn.com
 * 
 * inspired by and partly based on Adaptive Images by Matt Wilcox, https://github.com/MattWilcox/Adaptive-Images
 *
 *
 *
 * Configure values below
 *
 *
 * ROOT_PATH: Document Root
 * file system path
 */
define('ROOT_PATH', ($_SERVER['DOCUMENT_ROOT'][strlen($_SERVER['DOCUMENT_ROOT'])-1] == '/')?substr($_SERVER['DOCUMENT_ROOT'], 0, -1):$_SERVER['DOCUMENT_ROOT']);
define('SITE_PATH', dirname($_SERVER['SCRIPT_NAME']));
/**
 * PATH_CACHE: directory where resized image versions are stored
 * file system path
 */
define('PATH_CACHE', dirname(__FILE__) . '/site/assets/cache/images_cache');
/**
 * PATH_PLACEHOLDER: placeholder image to be delivered before replacement
 * url
 */
define('URL_PLACEHOLDER', SITE_PATH . (strlen(SITE_PATH) == 1?'':'/') . '/site/templates/images/transparent.gif');
/**
 * pixel interval to determine width of image to be served (width = ceil(imagewidth / interval) * inteval)
 * the smaller the number, the more versions of each image are likely to be generated and cached
 * in number of pixels
 */
define('RES_DEFAULT', 800); 
/**
 * JPEG quality
 * int value
 */
define('RES_JPEG_QUALITY', 50);
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
 * Log image generation for debug
 */
define('DEBUG', false);


/**
 * Modify below on your own risk
 */
$error = 0;
$url = parse_url(urldecode(isset($_GET['url'])?$_GET['url']:$_SERVER['REQUEST_URI']), PHP_URL_PATH);
if(!preg_match("#(\.s([0-9]+)\.p([0-9]+)\.r([0-9]+))\.([a-z]+)$#i", $url, $match) && $_COOKIE['responsive']) {
	// redirect to placeholder
	header('Location: ' . URL_PLACEHOLDER);
	exit();
} else {
	// gather path information
	$swidth = $match[2];
	$pwidth = $match[3];
	$pxratio = $match[4];
	$url = str_replace($match[1], '', $url);
	$path = (substr($path, 0, -1) == '/'?substr(dirname($url), 1):dirname($url));
	if(strpos($path, SITE_PATH) === false) {
        $path = SITE_PATH . $path;
	} 
	$path = ROOT_PATH . $path; 
	$path .= '/' . basename($url);
	if(file_exists($path)) {
		// determine target width
		$max_width = RES_DEFAULT;
		if($pwidth > 0) {
			$max_width = ceil($pwidth * $pxratio);
		} elseif($swidth > 0) {
			$max_width = ceil($swidth * $pxratio);
		}
		$error = deliverImage($path, $max_width)?3:0;	
	} else {
		// debug purposes
		if(DEBUG) {
			logMsg(sprintf('Image file %s not found.', $path));
		}
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
	if(!file_exists($path)) {
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

	// debug purposes
	if(DEBUG) {
		logMsg(sprintf('Looking for %s to send for requested image %s.', $cache_path, $src_path));
	}

	if(!file_exists($cache_path) || isOutdated($src_path, $cache_path)) {
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

		// debug purposes
		if(DEBUG) {
			logMsg(sprintf('Created %s from %s with dimensions %d x %d pixels.', $cache_path, $src_path, $dst_width, $dst_height));
		}
	}
	return $cache_path;
}

// check if source image was updated
function isOutdated($src_path, $cache_path) {
	return filectime($src_path) > filectime($cache_path)?true:false;
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
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', filectime($filename)).' GMT');
	header('Cache-control: public');
	header('Content-Length: '.filesize($filename));
	readfile($filename);
	exit();
}

function logMsg($line) {
	if((is_string($line) && strlen($line) > 0) && ($f = fopen('./responsive-images.log', 'a'))) {
		fwrite($f, date('d/m/y H:i:s').' : '.$line."\n");
		fclose($f);
		return true;
	} else {
		return false;
	}
}
?>