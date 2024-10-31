jQuery( document ).ready( function ( $ ) {
	'use strict';

	var $body = $('body');
	var $html = $('html');

	$( '.quickviewwoo-button-js' ).magnificPopup( {
		type: 'ajax',
		mainClass: 'quickviewwoo-modal',
		callbacks: {
			beforeOpen: function () {
				$html.addClass('quickviewwoo-modal-open');
			},
			afterClose: function () {
				$html.removeClass('quickviewwoo-modal-open');
			},
			ajaxContentAdded: function () {
				// Loaded content also available from this.content

				var $container = $( '.quickviewwoo-product' );
				var $button    = $container.find( '.single_add_to_cart_button' );

				imagesLoaded( $container, function () {
					if ( typeof $.fn.wc_product_gallery !== 'undefined' ) {
						$container.find( '.woocommerce-product-gallery' ).each( function () {
							$( this ).wc_product_gallery();
						} );
					}
				} );

				$container.find( 'form.variations_form' ).each( function() {
					$( this ).wc_variation_form();
				} );

				$button.addClass( 'add_to_cart_button ajax_add_to_cart' );
				$container.on( 'change', 'input[name="quantity"]', function() {
					$button.data( 'quantity', $( this ).val() );
				} );
				$container.on( 'woocommerce_variation_has_changed', 'form.variations_form', function() {
					$button.data( 'variation_id', $container.find( 'input[name="variation_id"]' ).val() );
				} );

				$container.on( 'click', '.single_add_to_cart_button', function( e ) {
					e.preventDefault();
					var $button = $( this );
					var $form   = $button.parents( 'form.cart' );

					var form_data = $form.serializeArray().reduce( function ( obj, val ) {
						obj[ val.name ] = val.value;
						return obj;
					}, {} );

					// There's no specific ajax action to call. The current woocommerce page will process the call as a normal form request.
					$.ajax( {
						url: location.href,
						data: form_data,
						type: 'post',
						beforeSend: function() {
							$button.attr( 'disabled', true );
							$container.block( {
								message: null,
								overlayCSS: {
									background: '#fff',
									opacity: 0.6
								}
							} );

						},
						success: function () {
							$button.attr( 'disabled', false );
							// $container.unblock();
							$( document.body ).trigger( 'wc_fragment_refresh' );

							$container.block( {
								message: 'Product added to cart!',
								overlayCSS: {
									background: '#fff',
									opacity: 0.6
								}
							} );

							setTimeout( function() {
								$container.unblock();
								$.magnificPopup.close();
							}, 3000);
						}
					} );
				} );
			},
		}
	} );

} );
