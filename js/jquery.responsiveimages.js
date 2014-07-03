/*! Copyright (c) 2013 Oliver Wehn (http://www.oliverwehn.com)
 * Licensed under MIT, GPL
 *
 * Version: 1.0.0
 * 07/03/2014
 *
 * Requires jQuery framework
 */
(function($, window, document, undefined) {
  "use strict";

  var pluginName = 'responsiveImages',
    defaults = {
      respondToResize: false,
      respondToUpscaleOnly: true,
      useParentWidth: true,
      resolutionInterval: 50,
      imgSelector: 'img',
      loadingClass: null,
      isMobile: false,
      callOnUpdate: null,
      callOnEach: null,
      debug: false
    };

  $.responsiveImages = function(elements, options) {
    var $elements = $(elements);
    this.options = $.extend({}, defaults, options);
    this.$images = $elements.find(this.options.imgSelector);
    if(this.$images.length == 0) {
        this.$images = $elements.filter(this.options.imgSelector);image
    }
    this._defaults = defaults;
    this._name = pluginName;
    this.loading = 0;
    this.initialized = false;
    this.window = this.getWindowSize();

    this.init();

  };

  $.responsiveImages.prototype = {
    init: function() {
      this.debugLog('Initializing Responsive Images ...');
      this.updatePaths();
      if (this.options.respondToResize) {
        this.resizeTimer = null;
        var _this = this;
        $(window).on('resize orientationchange', function() {
          if (_this.resizeTimer !== null) {
            window.clearTimeout(_this.resizeTimer);
          }
          _this.resizeTimer = window.setTimeout(function() {
            _this.callOnResize.call(_this);
            _this.resizeTimer = null;
          }, 500);
        });
      }
      if (this.$images.length === 0) {
        this.initialized = true;
      }
      this.callOnInit();
    },

    // append elements
    append: function(elements, updatePaths) {
      var $elements = $(elements),
        found = [],
        _this = this;
      $elements.each(function() {
        $(this).find('img').each(function() {
          _this.$images = _this.$images.add($(this));
          found.push($(this));
        });
      });
      updatePaths = updatePaths || true;
      var $found = $(found.map(function(el) {
        return el[0];
      }));
      this.$images.add($found);
      this.debugLog('Appended ' + $found.length + ' images, new count: ' + this.$images.length + '.');
      if (updatePaths) {
        this.updatePaths($found);
      }
    },

    // Updates paths of all matched image elements
    updatePaths: function($images) {
      this.debugLog('Going to update paths.');
      var _this = this;
      $images = $images || _this.$images;
      var options = _this.options,
        swidth = 0;
      if (typeof window.orientation !== undefined) {
        swidth = Math.ceil(((window.orientation === 90 || window.orientation === -90) ? screen.height : screen.width) / options.resolutionInterval) * options.resolutionInterval;
      } else {
        swidth = Math.ceil(screen.width / options.resolutionInterval) * options.resolutionInterval;
      }
      _this.debugLog('Starting to process ' + $images.length + ' images.');
      if ($images.length > 0) {
        $images.each(function(i, img) {
          _this.processImage($(img));
        });
      } else {
        _this.callOnUpdate();
      }
    },

    // Process image
    processImage: function($img) {
      var _this = this;
      var options = _this.options;
      var $parent = $img.parent();
      var pwidth = 0,
        swidth = this.getWindowSize().width;
      var pxratio = Math.round(window.devicePixelRatio) || 1;

      // ie cache fix
      var cache_fix = '?' + Math.round((new Date()).getTime() / 10000);

      while ((typeof $parent.css !== undefined) && ($parent.css('display') !== 'block')) {
        $parent = $parent.parent();
      }
      if (typeof $parent.width !== undefined) {
        pwidth = Math.ceil($parent.width() / options.resolutionInterval) * options.resolutionInterval;
      }
      if (pwidth < $img.width()) {
        pwidth = Math.ceil($img.width() / options.resolutionInterval) * options.resolutionInterval;
      }
      var src = '';
      var curr_src = $img.attr('src');

      if (_this.options.isMobile) {
        if (!(src = $img.data('mobile-src')) && !(src = $img.data('src'))) {
          src = $img.attr('data-mobile-src') || $img.attr('src');
          $img.data('mobile-src', src);
        }
        $img.addClass('mobile');
      } else {
        if ($img.hasClass('mobile')) {
          $img.removeClass('mobile');
        }
        if (!(src = $img.data('src'))) {
          src = $img.attr('src');
          $img.data('src', src).removeAttr('src');
        }
      }
      if (src) {

        var cwidth = $img.data('width') || null;
        if (
          cwidth === null ||
          (
            cwidth !== null &&
            (
              (pwidth > 0 && options.useParentWidth && pwidth > cwidth) ||
              ((pwidth === 0 || !options.useParentWidth) && swidth > cwidth)
            )
          )
        ) {

          var resp_src = _this.getURL(src, {
            swidth: swidth,
            pwidth: pwidth,
            pxratio: pxratio
          });
          resp_src += cache_fix;

          if (curr_src !== resp_src) {

            _this.debugLog('Processing ' + src);
            if (_this.options.loadingClass) {
              $img.addClass(_this.options.loadingClass);
            }

            var pre_img = new Image();
            $(pre_img)
              .load(function() {
                _this.debugLog('Responsive Images: Finished loading image ' + this.src + '. ' + _this.loading + ' to go!');
                var width = 0;
                if (typeof this.naturalWidth !== undefined) {
                  width = this.naturalWidth;
                } else {
                  width = this.width;
                }
                width = width / pxratio;
                $img
                  .attr('src', $(this).attr('src'))
                  .data('width', width);
                _this.loading -= 1;

                if (_this.options.loadingClass) {
                  $img.removeClass(_this.options.loadingClass);
                }
                if (typeof _this.options.callOnEach === 'function') {
                  _this.options.callOnEach.call(_this, $img);
                }
                if (_this.loading === 0) {
                  _this.callOnUpdate();
                }
              })
              .attr('src', resp_src);
            _this.loading += 1;
            _this.debugLog('Responsive Images: Initiated loading image ' + _this.loading + ': ' + resp_src);
            if (!_this.initialized) {
              _this.initialized = true;
            }
          }
        }
      }
    },

    // Get window dimensions
    getWindowSize: function() {
      var orientation = Math.abs(window.orientation);
      return {
        width: orientation === 90 ? screen.height : screen.width,
        height: orientation === 90 ? screen.width : screen.height,
        orientation: window.orientation || 0
      };
    },

    // Generates new URL name from original
    getURL: function(original_url, params, debug) {
      var swidth = params.swidth || 0,
        pwidth = params.pwidth || 0,
        pxratio = params.pxratio || 1;
      var url = original_url.replace(/(\.[a-z]+)$/, '.s' + swidth + '.p' + pwidth + '.r' + pxratio + '$1');
      return url;
    },

    // call onInit
    callOnInit: function() {
      if (typeof this.options.callOnInit === 'function') {
        this.options.callOnInit.call(this);
        this.debugLog('Responsive Images: Callback on initialization was called.');
      }
    },

    // call onUpdate
    callOnUpdate: function() {
      if (typeof this.options.callOnUpdate === 'function') {
        this.options.callOnUpdate.call(this, this.$images, this.initialized);
        this.debugLog('Responsive Images: Callback on update was called.');
      }
    },

    // call onResize
    callOnResize: function() {
      var new_dimensions = this.getWindowSize();
      if (new_dimensions.width > this.window.width || !this.respondToUpscaleOnly) {
        this.updatePaths();
      }
    },

    // logs to console if option debug is true
    debugLog: function(msg) {
      if (this.options.debug) {
        console.log(msg);
      }
    }
  };

  $.fn[pluginName] = function(options) {
    options = options || {};
    if (options.debug) {
      console.log('Called Responsive Images constructor on ' + (this.length > 1 ? this.length + ' elements' : (this[0].tagName + (this.attr('id') ? '#' + this.attr('id') : '') + (this.attr('class') ? '.' + this.attr('class').split(' ').join('.') : ''))));
    }
    var instance = null;
    if (typeof options === 'object') {
      instance = options.reset ? null : $.data(this, 'plugin_' + pluginName);
      if (!instance) {
        instance = new $.responsiveImages(this, options);
        $.data(this, 'plugin_' + pluginName, instance);
      }
      return this;
    } else {
      instance = $.data(this, 'plugin_' + pluginName);
      if (instance && typeof $.responsiveImages.prototype[options] === 'function') {
        var args = Array.prototype.slice.call(arguments, 1);
        return $.responsiveImages.prototype[options].apply(instance, args);
      }
      return null;
    }
  };
})(jQuery, window, document);
