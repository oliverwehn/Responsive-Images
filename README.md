Responsive Images V.1.0.0
=========================

This approach to deliver content as well as resolution specific images combines some ideas from other strategies like Matt Wilcox’ Adaptive Images and others. Why I started this? Because I was never happy about scaling images just down to screen size, because the most images are much smaller than that. So I tried to develop a strategy to match the image dimensions to the layout context it’s going to be presented in – without hacking document structure with comments and document.write. If you got some suggestions or feedback, let me know! 

It combines a cookie, a php script and a jQuery plugin to serve the images the optimal size. What do we need all the stuff for?

Cookie
------

The cookie set at the beginning of the page load (inline in the head of your document would be the best place for it) just stores the value “responsive=1” to be sent with all http requests. It’s the switch turning the whole thing on.
```HTML
<script>
  document.cookie='responsive=1';
</script>
```

Rewrite
-------

In your .htaccess file all image requests will be rewritten via mod_rewrite to be directed to the PHP script “responsive-images.php” in your site’s root directory. 
```htaccess
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule \.(?:jpe?g|png|gif)$ responsive-images.php [QSA,L]
</IfModule>
```

PHP Script
----------

The PHP script delivers all the images as requested. If the cookie isn’t set, the script will deliver the requested image (down-)scaled to a predefinded default width if needed. But if the cookie is set–and if the image requests weren’t prevented on client side–the php script delivers a placeholder image (e.g. a 1px transparent png). As soon, as the jQuery plugin takes over and starts to request the images with information on resolution, pixel ratio and layout-specific image dimensions embedded into the filename (```my/path/image.s1280.p540.r2.jpg?5342542```, with s being the screen width, p the images first parent block element and r the device’s pixel ratio), the script will act differently. In this case it will generate and cache a resized image accordingly to the given pixel widths and pixel ratio. To keep the number of cached files low, the width value will be ceiled to match the next multiple of a predefined pixel interval (like 50px, so 432px request will lead to 450px image width being generated, stored and delivered).


jQuery Plugin
-------------

The jQuery Plugin just iterates through all images you want to be matched, stores the original source as data for each img element and removes the original src attribute. Then it tries to determine the width of the image’s closest parent block level element and adds the values for screen width and width of the parent element as well as pixel ratio to the filename in the original url and sets it as new image source - of course not without preloading the image from the new url before, which will be handled by the our nice little PHP script.

By default the plugin triggers a bigger image only being loaded on window resize if the current image would have to be upscaled.

Since v. 1.0.0 it’s possible to pass three callback functions within the options object.
* callOnInit() is triggered on initialization of the plugin.
* callOnEach($image) is called each time, an image was processed by the plugin after it was loaded. As the callback is passed the jQuery object of the image, it’s great for fading in images after they were loaded.
* callOnUpdate() is called each time after the images handled by the plugin were updated (e.g. after initialization or after viewport resize).

It’s also possible to just encode an img url if an image has to be created dynamically on the fly, like:
```Javascript
var $img = $('<img />');
$img.attr('src', $img.responsiveImages('getURL', '/images/catpic.jpg', { swidth: screen.width, pwidth: $('.future-parent').width(), pxratio: window.devicePixelRatio || 1 }));
```

I was thinking about about providing a framework-agnostic pure javascript solution. But with the Responsive Image Element coming up and the current solution working just fine with my setups, I haven’t found the time and motivation to go down this path. If you are interested in turning the plugin vanilla, I’d be happy to add a link to your repo or get a pull request.


How do I use it?
----------------

Put this into the head section of your page:
```HTML
<script>
	document.cookie='responsive=1';
</script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script src="url/to/js/jquery.responsive-images.min.js"></script>
<script>
	$('.responsive-images').responsiveImages({ /* your options */ });
</script>
```

Add a rewrite to you .htaccess (Apache only), combined with some RewriteCond to make it fit your needs:
```Htaccess
RewriteRule \.(?:jpe?g|png|gif)$ responsive-images.php [QSA,L]
```

Put ```responsive-images.php``` into place, as well as the plugin ```jquery.responsiveimages.min.js```.

Create a new directory where your images will be cached. Change the settings in responsive-images.php to match your setup and need.

Test it, give feedback, contribute.