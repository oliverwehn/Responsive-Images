Responsive Images V.0.8
=======================

BE WARNED: THIS IS AN ONGOING EXPERIMENT AND HASN’T BEEN REALLY TESTED, YET! 

This approach to deliver content as well as resolution specific images combines some ideas from other strategies like Matt Wilcox’ Adaptive Images and others. Why I started this? Because I was never happy about scaling images just down to screen size, because the most images are much smaller than that. So I tried to develop a strategy to match the image resolution to the layout context it’s presented in – without hacking document structure with comments and document.write. If you got some suggestions or feedback, let me know! I’m currently testing this on my (German) blog (http://marginalia.oliverwehn.com/) , if you want to see the whole thin in action.

It combines a cookie, a php script and a jQuery plugin to serve the images the optimal size. What do we need all the stuff for?

Cookie
------

The cookie just stores the value “responsive=1” to be sent with all http requests.


PHP Script
----------

All image requests are rewritten to the php script file. If the cookie isn’t set, the php script delivers the requested image scaled to a predefinded max width if needed. But if the cookie is set–and if the image requests weren’t prevented on client side–the php script delivers a placeholder image (or e.g. an animated loading gif). As soon, as the request is made with information on resolution and layout-specific image dimensions within the filename (int screen width and/or int parent width and int pixel ratio, the script will act differently. In this case it will generate and cache a resized image accordingly to the given pixel widths and pixel ratio. To keep the number of cached files low, the width value will be ceiled to match a multiple of a predefined pixel interval (like 50px, so 432px request will lead to 450px image width being delivered).


jQuery Plugin
-------------

The jQuery Plugin just iterates through all images you want to be matched, stores the original source as data for each img element and removes the original src attribute. Then it tries to determine the width of the next image’s parent  block level element and adds the values for screen width and width of parent element as well as pixel ratio to the filename in the original url and sets it as new image source - of course not without preloading the image from the new url before. 

By default the plugin triggers a bigger image only being loaded on window resize if the current image would have to be upscaled.

Since v. 0.8. it’s possible to pass a callback function, which is triggered when all scaled images were loaded and sources were set successfully.
It’s also possible to just encode an img url if an image has to be created dynamically on the fly, like:

var $img = $('<img />');
$img.attr('src', $img.responsiveImages('getURL', '/images/catpic.jpg', { swidth: screen.width, pwidth: $('.future-parent').width(), pxratio: window.devicePixelRatio || 1 }));

I thought about providing a framework-agnostic pure javascript solution. But as I’m not that deep into JS, I decided to go with jQuery to get results in no time. If you are interested in turning the plugin into a standalone version, I’d be happy to get your pull request.


So what exactly happens?
------------------------

If you have no javascript enabled? The cookie won’t be set, the jQuery plugin won’t work and the php script will deliver an image in your default max width.

If you have javascript enabled, the magic will start to work. The cookie will be set and send with every request for an image to the server. The php script will redirect all these requests to your placeholder or loading image. None of your original images is actually loaded on page load. As soon as the jQuery plugin comes into play, it detects the width values (screen, parent element, pixel ratio) and re-requests the image urls with providing the width values as get parameters. The php script generates and caches (if it doesn’t exist, yet) an appropirate image and sends it back to the browser where the jQuery plugin puts it into place as soon as it has been loaded completely.


How do I use it?
----------------

Put this into the head section of your page:

<script>
	document.cookie='responsive=1';
</script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script src="url/to/js/jquery.responsive-images.min.js"></script>
<script>
	$('.responsive-images').responsiveImages({ /* your options */ }, function() { /* your callback */ });
</script>


Add a rewrite to you .htaccess (Apache only), combined with some RewriteCond to make it fit your needs:

RewriteRule \.(?:jpe?g|png)$ responsive-images.php [QSA,L]

Put responsive-images.php into place, as well as the plugin jquery.responsive-images.min.js.

Create a new directory where your images will be cached. Change the settings in responsive-images.php to match your setup and need.

Test it, give feedback, contribute.


The Future
----------

Testing will continue while I’ll be working on making the whole thing more flexible and offering more config options.