/*! WooCommerce Recent Products Widget Via Ajax - (C) Pino Ceniccola - GPLv3 - https://github.com/pinoceniccola/woocommerce-recently-viewed-ajax */

(function ($) {
	var recentlyViewed = (document.cookie.match(/^(?:.*;)?\s*woocommerce_recently_viewed\s*=\s*([^;]+)(?:.*)?$/)||[,null])[1],
		widgetPlaceholder = $('.widget_recently_viewed_products'),

		// Since 0.2.0: Now we are tracking the current product by CSS Classes and DOM
		//_current = (widgetPlaceholder.length) ? +widgetPlaceholder.data('current') : 0;
		_current = $('body.single-product .product.type-product[id]');
		_current = (_current.length) ? parseInt(_current[0].id.match(/(\d+)/)[0], 10) : 0;

	if (recentlyViewed && widgetPlaceholder.length) {
		$(window).one('scroll',function() {
		$.post(
			recently_viewed_object.ajaxurl, {
			action: 'wc_recent_products',
			number: widgetPlaceholder.data('number') },
			function( response ) {
				$('.widget_recently_viewed_products .product_list_widget').html( response );
				widgetPlaceholder.css('display','block');
				_updateCookie(_current);
			});
		});
	} else {
		_updateCookie(_current);
	}

	function _updateCookie(_current) {
		if (_current) {
			if ( recentlyViewed == null  ) {
				var _viewed_products = [];
			} else {
				var _viewed_products =  $.map(recentlyViewed.split('|'), function(value){return parseInt(value, 10);});
			}

			if (_viewed_products.indexOf(_current) === -1) {
				_viewed_products.push(_current);

				if ( _viewed_products.length > 15 ) {
					var _shifted = _viewed_products.shift();
				}

				// Store for session only.
				document.cookie = "woocommerce_recently_viewed="+_viewed_products.join('|')+";path="+recently_viewed_object.cookiepath+';SameSite=Strict';
			}

		}
	}

})(jQuery);
