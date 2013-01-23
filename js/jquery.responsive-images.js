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
			useParentWidth: true
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

	$.responsiveImages.prototype.updatePaths = function() {
		var swidth = screen.width;
		var pxratio = window.devicePixelRatio || 1;
		var options = this.options;
		$(this.elements).filter('img').each(function(i, img) {
			var $img = $(img);
			var pwidth = $img.parent().width();
			if(pwidth < $img.width()) {
				pwidth = $img.width();
			}
			var src = '';
			if(!(src = $img.attr('data-src'))) {
				$img.attr('data-src', $img.attr('src'));
				src = $img.attr('data-src');
			}
			var src = $img.attr('data-src');
			var cwidth = $img.data('width') || null;
			if(
				cwidth == null ||
				(pwidth > 0 && options.useParentWidth && pwidth > cwidth) ||
				((pwidth == 0 || !options.useParentWidth) && swidth > cwidth)
			) {
				var pre_img = new Image();
				$(pre_img)
				.load(function() {
					$img
					.attr('src', $(this).attr('src'))
					.data('width', $(this)[0].width);
				})
				.attr('src', src + '?swidth=' + swidth + ((pwidth > 0 && options.useParentWidth)?'&pwidth=' + pwidth:'') + '&pxratio=' + pxratio);
			}
		});
	};

	$.fn[pluginName] = function(options, callback) {
		var instance = $.data(this, 'plugin_' + pluginName);
		if(!instance) {
			$.data(this, 'plugin_' + pluginName, new $.responsiveImages(this, options, callback));		
		}
		return this;
	};


})( jQuery, window, document );