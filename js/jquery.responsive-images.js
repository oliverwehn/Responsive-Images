/*! Copyright (c) 2013 Oliver Wehn (http://www.oliverwehn.com)
 * Licensed under MIT, GPL
 *
 * Version: 0.1.0
 * 
 * Requires jQuery framework
 */
 ;(function ( $, window, document, undefined ) {

	var pluginName = 'responsiveImages',
		defaults = {
			respondToResize: false,
			respondToUpscaleOnly: true,
			useParentWidth: true,
			resolutionInterval: 50 
		};

	$.responsiveImages = function( elements, options ) {
		this.elements = elements;
		this.options = $.extend( {}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;

		this.init(options);

	}

	$.responsiveImages.prototype.init = function () {
		this.updatePaths();
		if(this.options.respondToResize) {
			var to = null;
			var instance = this;
			$(window).resize(function() {
				if(to != null) {
					window.clearTimeout(to);
				}
				to = window.setTimeout(function() {
					instance.updatePaths();
				}, 100);
			});
		}
	};

	// Updates paths of all matched image elements
	$.responsiveImages.prototype.updatePaths = function() {
		var instance = this;
		var options = instance.options;
		var pxratio = window.devicePixelRatio || 1;
		var swidth = Math.ceil(screen.width / options.resolutionInterval) * options.resolutionInterval;
		$(instance.elements).filter('img').each(function(i, img) {
			var $img = $(img);
			var pwidth = Math.ceil($img.parent().width() / options.resolutionInterval) * options.resolutionInterval;
			if(pwidth < $img.width() && $img.width() < $img[0].width) {
				pwidth = Math.ceil($img.width() / options.resolutionInterval) * options.resolutionInterval;
			}
			var src = '';
			if(!(src = $img.data('src'))) {
				$img.data('src', $img.attr('src'));
				src = $img.data('src');
			}
			var src = $img.data('src');
			var cwidth = $img.data('width') || null;
			if(
				cwidth == null ||
				(pwidth > 0 && options.useParentWidth && pwidth > cwidth) ||
				((pwidth == 0 || !options.useParentWidth) && swidth > cwidth)
			) {
				var hash = instance.getHash(swidth + '.' + pwidth + '.' + pxratio);
				var pre_img = new Image();
				$(pre_img)
				.load(function() {
					$img
					.attr('src', $(this).attr('src'))
					.data('width', $(this)[0].width);
				})
				.attr('src', src.replace(/(\.[a-z]+)$/, '.' + hash + '$1') + '?swidth=' + swidth + ((pwidth > 0 && options.useParentWidth)?'&pwidth=' + pwidth:'') + '&pxratio=' + pxratio);
			}
		});
	};

	// Generates hash string for url to make it unique for CDNs not caring about GET parameters
	// based on http://werxltd.com/wp/2010/05/13/javascript-implementation-of-javas-string-hashcode-method/
	$.responsiveImages.prototype.getHash = function(str) {
	    var hash = 0, i, char;
	    if (str.length == 0) return hash;
	    for (i = 0; i < str.length; i++) {
	        char = str.charCodeAt(i);
	        hash = ((hash<<5)-hash)+char;
	        hash = hash & hash;
	    }
	    hash = hash * -1;
	    return hash.toString(16);
	};

	$.fn[pluginName] = function(options, callback) {
		var instance = $.data(this, 'plugin_' + pluginName);
		if(!instance) {
			$.data(this, 'plugin_' + pluginName, new $.responsiveImages(this, options, callback));		
		}
		return this;
	};


})( jQuery, window, document );