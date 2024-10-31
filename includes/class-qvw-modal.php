<?php
class QVW_Modal {
	public function init() {
		add_action( 'wp_ajax_quickviewwoo', array( $this, 'ajax_get_product_content' ) );
		add_action( 'wp_ajax_nopriv_quickviewwoo', array( $this, 'ajax_get_product_content' ) );

		add_action( 'quickviewwoo_modal_image_area', 'QVW_Modal::template_sale_flash', 10 );
		add_action( 'quickviewwoo_modal_image_area', 'QVW_Modal::template_product_image', 20 );

		add_action( 'quickviewwoo_modal_content_area', 'QVW_Modal::template_title', 10 );
		add_action( 'quickviewwoo_modal_content_area', 'QVW_Modal::template_rating', 20 );
		add_action( 'quickviewwoo_modal_content_area', 'QVW_Modal::template_price', 30 );
		add_action( 'quickviewwoo_modal_content_area', 'QVW_Modal::template_excerpt', 40 );
		add_action( 'quickviewwoo_modal_content_area', 'QVW_Modal::template_add_to_cart', 50 );
		add_action( 'quickviewwoo_modal_content_area', 'QVW_Modal::template_meta', 60 );

		add_action( 'quickviewwoo_modal_gallery_area', 'QVW_Modal::template_gallery', 10 );
	}

	/**
	 * Enqueues frontend scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
			wp_enqueue_script( 'zoom' );
			add_filter( 'woocommerce_single_product_zoom_enabled', '__return_true' );
			wp_enqueue_script( 'flexslider' );
			add_filter( 'woocommerce_single_product_flexslider_enabled', '__return_true' );
			wp_enqueue_script( 'photoswipe-ui-default' );
			wp_enqueue_style( 'photoswipe-default-skin' );
			add_filter( 'woocommerce_single_product_photoswipe_enabled', '__return_true' );
			add_action( 'wp_footer', 'woocommerce_photoswipe' );

			wp_enqueue_script( 'wc-add-to-cart' );
			wp_enqueue_script( 'wc-add-to-cart-variation' );
			wp_enqueue_script( 'wc-single-product' );

	}

	public function ajax_get_product_content() {
		if ( isset( $_GET['pid'] ) ) {
			$pid = intval( $_GET['pid'] );

			global $post;
			global $product;

			$post    = get_post( $pid );
			$product = wc_get_product( $pid );

			setup_postdata( $post );


			if ( in_array( $product->get_type(), array( 'simple' ), true ) ) {
				add_action( 'woocommerce_after_add_to_cart_button', 'QuickViewWoo::add_hidden_inputs' );
			}

			?>
			<div class="quickviewwoo-product">

				<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

					<?php
						/**
						 * Hook: quickviewwoo_modal_image_area.
						 *
						 * @hooked QVW_Modal::template_sale_flash - 10
						 * @hooked QVW_Modal::template_product_image - 20
						 */
						do_action( 'quickviewwoo_modal_image_area' );
					?>

					<div class="quickviewwoo-summary quickviewwoo-entry-summary">
						<?php
							/**
							 * Hook: quickviewwoo_modal_content_area.
							 *
							 * @hooked QVW_Modal::template_title - 10
							 * @hooked QVW_Modal::template_rating - 20
							 * @hooked QVW_Modal::template_price - 30
							 * @hooked QVW_Modal::template_excerpt - 40
							 * @hooked QVW_Modal::template_add_to_cart - 50
							 * @hooked QVW_Modal::template_meta - 60
							 */
							do_action( 'quickviewwoo_modal_content_area' );
						?>
					</div>

				</div>
			</div>
			<?php
		}

		wp_die();
	}

	public static function template_sale_flash() {
		global $post;
		global $product;

		if ( $product->is_on_sale() ) {
			echo apply_filters( 'woocommerce_sale_flash', '<span class="quickviewwoo-onsale">' . esc_html__( 'Sale!', 'quick-view-woo' ) . '</span>', $post, $product );
		}
	}

	public static function template_product_image() {
		global $product;

		$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
		$post_thumbnail_id = $product->get_image_id();
		$wrapper_classes   = apply_filters( 'woocommerce_single_product_image_gallery_classes', array(
			'quickviewwoo-product-gallery',
			'woocommerce-product-gallery',
			'woocommerce-product-gallery--' . ( $product->get_image_id() ? 'with-images' : 'without-images' ),
			'woocommerce-product-gallery--columns-' . absint( $columns ),
			'images',
		) );
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">
			<figure class="woocommerce-product-gallery__wrapper">
				<?php
				if ( $product->get_image_id() ) {
					$html = self::wc_get_gallery_image_html( $post_thumbnail_id, true );
				} else {
					$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
					$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'quick-view-woo' ) );
					$html .= '</div>';
				}

				echo apply_filters( 'quickviewwoo_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped

				/**
				 * Hook: quickviewwoo_modal_content_area.
				 *
				 * @hooked QVW_Modal::template_gallery - 10
				 */
				do_action( 'quickviewwoo_modal_gallery_area' );
				?>
			</figure>
		</div>
		<?php
	}

	public static function template_gallery() {
		global $product;

		$attachment_ids = $product->get_gallery_image_ids();

		if ( $attachment_ids && $product->get_image_id() ) {
			foreach ( $attachment_ids as $attachment_id ) {
				echo apply_filters( 'quickviewwoo_single_product_image_thumbnail_html', self::wc_get_gallery_image_html( $attachment_id ), $attachment_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
		}
	}

	/**
	 * Get HTML for a gallery image.
	 *
	 * This differs from wc_get_gallery_image_html() in that it doesn't add a link to the image.
	 * It also forces $image_size to 'woocommerce_single'.
	 *
	 * @see wc_get_gallery_image_html()
	 *
	 * Hooks: woocommerce_gallery_thumbnail_size, woocommerce_gallery_image_size and woocommerce_gallery_full_size accept name based image sizes, or an array of width/height values.
	 *
	 * @since 3.3.2
	 * @param int  $attachment_id Attachment ID.
	 * @param bool $main_image Is this the main image or a thumbnail?.
	 * @return string
	 */
	public static function wc_get_gallery_image_html( $attachment_id, $main_image = false ) {
		$flexslider        = (bool) apply_filters( 'woocommerce_single_product_flexslider_enabled', get_theme_support( 'wc-product-gallery-slider' ) );
		$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
		$thumbnail_size    = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
		$image_size        = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
		$full_size         = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
		$thumbnail_src     = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
		$full_src          = wp_get_attachment_image_src( $attachment_id, $full_size );
		$alt_text          = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
		$image             = wp_get_attachment_image(
			$attachment_id,
			$image_size,
			false,
			apply_filters(
				'woocommerce_gallery_image_html_attachment_image_params',
				array(
					'title'                   => _wp_specialchars( get_post_field( 'post_title', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
					'data-caption'            => _wp_specialchars( get_post_field( 'post_excerpt', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
					'data-src'                => esc_url( $full_src[0] ),
					'data-large_image'        => esc_url( $full_src[0] ),
					'data-large_image_width'  => esc_attr( $full_src[1] ),
					'data-large_image_height' => esc_attr( $full_src[2] ),
					'class'                   => esc_attr( $main_image ? 'wp-post-image' : '' ),
				),
				$attachment_id,
				$image_size,
				$main_image
			)
		);

		return '<div data-thumb="' . esc_url( $thumbnail_src[0] ) . '" data-thumb-alt="' . esc_attr( $alt_text ) . '" class="woocommerce-product-gallery__image">' . $image . '</div>';
	}


	public static function template_title() {
		the_title( '<h1 class="quickviewwoo-product_title quickviewwoo-entry-title">', '</h1>' );
	}

	public static function fixed_filter_rating_class( $html, $rating, $count ) {
		return str_replace( 'class="star-rating"', 'class="quickviewwoo-star-rating"', $html );
	}

	public static function template_rating() {
		global $product;

		if ( ! wc_review_ratings_enabled() ) {
			return;
		}

		$rating_count = $product->get_rating_count();
		$review_count = $product->get_review_count();
		$average      = $product->get_average_rating();

		if ( $rating_count > 0 ) : ?>

			<div class="quickviewwoo-product-rating">
				<?php
					add_filter( 'woocommerce_product_get_rating_html', 'QVW_Modal::fixed_filter_rating_class', 10, 3 );
					echo wc_get_rating_html( $average, $rating_count ); // WPCS: XSS ok.
					remove_filter( 'woocommerce_product_get_rating_html', 'QVW_Modal::ffixed_filter_rating_class', 10 );
				?>
				<?php if ( comments_open() ) : ?>
					<?php //phpcs:disable ?>
					<a href="<?php echo esc_url( $product->get_permalink() ); ?>#reviews" class="quickviewwoo-review-link" rel="nofollow">(<?php printf( _n( '%s customer review', '%s customer reviews', $review_count, 'quick-view-woo' ), '<span class="count">' . esc_html( $review_count ) . '</span>' ); ?>)</a>
					<?php // phpcs:enable ?>
				<?php endif ?>
			</div>

		<?php endif;
	}

	public static function template_price() {
		global $product;

		?><p class="quickviewwoo-price"><?php echo $product->get_price_html(); ?></p><?php
	}

	public static function template_excerpt() {
		global $post;

		$short_description = apply_filters( 'woocommerce_short_description', $post->post_excerpt );

		if ( ! $short_description ) {
			return;
		}

		?>
		<div class="quickviewwoo-product-details__short-description">
			<?php echo $short_description; // WPCS: XSS ok. ?>
		</div>
		<?php
	}

	public static function template_add_to_cart() {
		global $product;
		do_action( 'woocommerce_' . $product->get_type() . '_add_to_cart' );
	}

	public static function template_meta() {
		global $product;
		?>
		<div class="quickviewwoo-product_meta">
			<?php do_action( 'woocommerce_product_meta_start' ); ?>

			<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ) ) ) : ?>

				<span class="sku_wrapper"><?php esc_html_e( 'SKU:', 'quick-view-woo' ); ?> <span class="sku"><?php echo ( $sku = $product->get_sku() ) ? $sku : esc_html__( 'N/A', 'quick-view-woo' ); ?></span></span>

			<?php endif; ?>

			<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'quick-view-woo' ) . ' ', '</span>' ); ?>

			<?php echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', count( $product->get_tag_ids() ), 'quick-view-woo' ) . ' ', '</span>' ); ?>

			<?php do_action( 'woocommerce_product_meta_end' ); ?>
		</div>
		<?php
	}

}
