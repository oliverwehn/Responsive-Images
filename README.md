Responsive Images
=================

BE WARNED: THIS IS AN ONGOING EXPERIMENT AND HASN’T BEEN REALLY TESTED, YET! 

This approach to deliver content as well as resolution specific images combines some ideas from other strategies like Matt Wilcox’ Adaptive Images and others. Why I started this? Because I was never happy about scaling images just down to screen size, because the most images are much smaller than that. So I tried to develop a strategy to match the image resolution to the layout context it’s presented in – without hacking document structure with comments and document.write. If you got some suggestions or feedback, let me know! I’m currently testing this on my (German) blog (http://marginalia.oliverwehn.com/) , if you want to see the whole thin in action.

It combines a cookie, a php script and a jQuery plugin to serve the images the optimal size. What do we need all the stuff for?

Cookie
------

The cookie just stores the value “responsive=1” to be sent with all http requests.


PHP Script
----------

All image requests are rewritten to the php script file. If the cookie isn’t set, the php script delivers the requested image scaled to a predefinded max width if needed. But if the cookie is set, the php script redirects all image requests (using a “301 temporary moved” redirect) to a placeholder image (or e.g. an animated loading gif). As soon, as the request is made with the GET parameters (int) swidth for “screen width” or (int) pwidth for “parent width”, the script will act differently. In this case it will generate and cache a resized image accordingly to the given pixel widths while prefering the parent width. To keep the number of cached files low, the width value will be ceiled to match a multiple of a predefined pixel interval.


jQuery Plugin
-------------

The jQuery Plugin just iterates through all images you want to be matched, stores the original url in a data-src attribute. Then it tries to determine the width of the image’s parent element and adds the parameters swidth (screen width) and pwidth (width of parent element) as well as pxratio (pixel ratio) to the original url in the src attribute - of course not without preloading the image from the new url before.To deal with different caching behaviours when it comes to content distribution networks (CDN), a unique hash is generated and inserted into the url, so the resized image will be cached in its resized version, even if GET parameters were ignored. 

By default the plugin triggers a bigger image only being loaded on window resize if the current image would have to be upscaled.

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
<script src="url/to/js/jquery.responsiveimages.min.js"></script>


Add a rewrite to you .htaccess (Apache only), combined with some RewriteCond to make it fit your needs:

RewriteRule \.(?:jpe?g|png)$ responsive-images.php [QSA,L]

Put responsive-images.php into place, as well as the plugin jquery.responsive-images.min.js.

Create a new directory where your images will be cached. Change the settings in responsive-images.php to match your setup and need.

Test it, give feedback, contribute.


The Future
----------

Testing will continue while I’ll be working on making the whole thing more flexible and offering more config options. Also I want to include SVG handling, so if there is SVG support, the php script will deliver the SVG image, otherwise it will send a appropriately scaled fallback image.