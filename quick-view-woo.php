<?php
/**
 * Plugin Name: CSSIgniter Quick View for WooCommerce
 * Plugin URI: https://www.cssigniter.com/plugins/quick-view-woo-pro/
 * Description: Quick View for WooCommerce
 * Author: The CSSIgniter Team
 * Author URI: https://www.cssigniter.com
 * Version: 1.1.1
 * Text Domain: quick-view-woo
 * Domain Path: languages
 *
 * Quick View Woo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Quick View Woo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Quick View Woo. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QuickViewWoo {

	/**
	 * QuickViewWoo version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public static $version = '1.1.1';

	/**
	 * Instance of this class.
	 *
	 * @var QuickViewWoo
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * The URL directory path (with trailing slash) of the main plugin file.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected static $plugin_url = '';

	/**
	 * The filesystem directory path (with trailing slash) of the main plugin file.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected static $plugin_path = '';

	/**
	 * Modal instance.
	 *
	 * @var QVW_Modal
	 * @since 1.0.0
	 */
	protected $modal;

	/**
	 * Modal instance.
	 *
	 * @var QVW_CSS
	 * @since 1.0.0
	 */
	protected $css;

	/**
	 * QuickViewWoo Instance.
	 *
	 * Instantiates or reuses an instance of QuickViewWoo.
	 *
	 * @since 1.0.0
	 * @static
	 * @see QuickViewWoo()
	 * @return QuickViewWoo - Single instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * QuickViewWoo constructor. Intentionally left empty so that instances can be created without
	 * re-loading of resources (e.g. scripts/styles), or re-registering hooks.
	 * http://wordpress.stackexchange.com/questions/70055/best-way-to-initiate-a-class-in-a-wp-plugin
	 * https://gist.github.com/toscho/3804204
	 *
	 * @since 1.0.0
	 */
	public function __construct() {}

	/**
	 * Kickstarts plugin loading.
	 *
	 * @since 1.0.0
	 */
	public function plugin_setup() {
		self::$plugin_url  = plugin_dir_url( __FILE__ );
		self::$plugin_path = plugin_dir_path( __FILE__ );

		load_plugin_textdomain( 'quick-view-woo', false, dirname( self::plugin_basename() ) . '/languages' );

		require_once untrailingslashit( $this->plugin_path() ) . '/includes/class-qvw-modal.php';
		$this->modal = new QVW_Modal();

		require_once untrailingslashit( $this->plugin_path() ) . '/includes/class-qvw-css.php';
		$this->css = new QVW_CSS();

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_woocommerce_required' ) );
			return;
		}

		// Initialization needed in every request.
		$this->init();

		// Initialization needed in admin requests.
		$this->admin_init();

		// Initialization needed in frontend requests.
		$this->frontend_init();

		do_action( 'quickviewwoo_loaded' );
	}

	/**
	 * Registers actions that need to be run on both admin and frontend
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		add_action( 'init', array( $this, 'register_scripts' ) );

		$this->modal->init();

		// This needs to be here, as it won't run during AJAX if placed in self::frontend_init().
		add_filter( 'woocommerce_add_to_cart_form_action', array( $this, 'add_to_cart_form_action' ) );

		$this->load_theme_support();

		do_action( 'quickviewwoo_init' );
	}


	/**
	 * Registers actions that need to be run on admin only.
	 *
	 * @since 1.0.0
	 */
	protected function admin_init() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );

		do_action( 'quickviewwoo_admin_init' );
	}

	/**
	 * Registers actions that need to be run on frontend only.
	 *
	 * @since 1.0.0
	 */
	protected function frontend_init() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 50 );

		$position      = self::get_options( 'quickviewwoo_button_position' );
		$position_info = self::get_button_position_info( $position );

		$hook     = apply_filters( 'quickviewwoo_button_hook_name', $position_info['action'] );
		$priority = intval( apply_filters( 'quickviewwoo_button_hook_priority', intval( $position_info['priority'] ) ) );

		add_action( $hook, array( $this, 'woo_add_quickview_button' ), $priority );

		do_action( 'quickviewwoo_frontend_init' );
	}

	public function should_show_button() {
		$return = true;

		if ( ( 'yes' !== self::get_options( 'quickviewwoo_button_show' ) ) ||
			( 'yes' !== self::get_options( 'quickviewwoo_button_max_compat' ) && ! ( is_woocommerce() || is_cart() ) ) ||
			( wp_is_mobile() && 'yes' !== self::get_options( 'quickviewwoo_button_show_mobile' ) ) ||
			( is_product() && 'yes' !== self::get_options( 'quickviewwoo_button_show_single' ) )
		) {
			$return = false;
		}

		return apply_filters( 'quickviewwoo_should_show_button', $return );
	}

	/**
	 * Register (but not enqueue) all scripts and styles to be used throughout the plugin.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		wp_register_style( 'jquery-magnific-popup', $this->plugin_url() . 'assets/vendor/magnific-popup/magnific.css', array(), '1.0.0' );
		wp_register_script( 'jquery-magnific-popup', $this->plugin_url() . 'assets/vendor/magnific-popup/jquery.magnific-popup.js', array( 'jquery' ), '1.0.0', true );

		wp_register_script( 'imagesLoaded', $this->plugin_url() . 'assets/vendor/imagesloaded/imagesloaded.pkgd.js', array( 'jquery' ), '4.1.3', true );

		wp_register_style( 'quick-view-woo', $this->plugin_url() . 'assets/css/frontend.css', array(
			'jquery-magnific-popup',
		), self::$version );
		wp_register_script( 'quick-view-woo', $this->plugin_url() . 'assets/js/frontend.js', array(
			'jquery',
			'jquery-magnific-popup',
			'imagesLoaded',
		), self::$version, true );
	}

	/**
	 * Enqueues frontend scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! $this->should_show_button() ) {
			return;
		}

		$this->modal->enqueue_scripts();

		wp_enqueue_style( 'quick-view-woo' );
		wp_enqueue_script( 'quick-view-woo' );

		wp_add_inline_style( 'quick-view-woo', $this->generate_css() );
	}

	public function add_settings_page( $settings ) {
		$settings[] = include untrailingslashit( $this->plugin_path() ) . '/includes/class-qvw-settings-quickviewwoo.php';

		return $settings;
	}

	public static function get_defaults( $option = false ) {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		$defaults = apply_filters( 'quickviewwoo_defaults', array(
			'quickviewwoo_button_show'        => 'yes',
			'quickviewwoo_button_show_mobile' => 'yes',
			'quickviewwoo_button_show_single' => 'yes',
			'quickviewwoo_button_max_compat'  => '',

			'quickviewwoo_button_text'     => __( 'Quick View', 'quick-view-woo' ),
			'quickviewwoo_button_classes'  => 'button',
			'quickviewwoo_button_position' => 'after-add-to-cart',

			'quickviewwoo_modal_container_width' => 960,

			'quickviewwoo_page_overlay_color'                => '',
			'quickviewwoo_content_bg_color'                  => '',
			'quickviewwoo_content_border_color'              => '',
			'quickviewwoo_content_text_color'                => '',
			'quickviewwoo_content_link_color'                => '',
			'quickviewwoo_content_link_hover_color'          => '',
			'quickviewwoo_content_rating_active_color'       => '',
			'quickviewwoo_content_rating_inactive_color'     => '',
			'quickviewwoo_content_button_bg_color'           => '',
			'quickviewwoo_content_button_text_color'         => '',
			'quickviewwoo_content_button_border_color'       => '',
			'quickviewwoo_content_button_hover_bg_color'     => '',
			'quickviewwoo_content_button_hover_text_color'   => '',
			'quickviewwoo_content_button_hover_border_color' => '',
		), $option );
		// phpcs:enable

		if ( false === $option ) {
			return $defaults;
		}

		$value = false;

		if ( array_key_exists( $option, $defaults ) ) {
			$value = $defaults[ $option ];
		}

		return apply_filters( 'quickviewwoo_get_default', $value, $option );
	}

	public static function get_button_position_info( $position = false ) {
		$positions = apply_filters( 'quickviewwoo_button_positions_info', array(
			'before-thumbnail'   => array(
				'label'    => __( 'Before thumbnail', 'quick-view-woo' ),
				'action'   => 'woocommerce_before_shop_loop_item',
				'priority' => 5,
			),
			'before-title'       => array(
				'label'    => __( 'Before title', 'quick-view-woo' ),
				'action'   => 'woocommerce_shop_loop_item_title',
				'priority' => 5,
			),
			'before-rating'      => array(
				'label'    => __( 'Before rating', 'quick-view-woo' ),
				'action'   => 'woocommerce_after_shop_loop_item_title',
				'priority' => 3,
			),
			'before-price'       => array(
				'label'    => __( 'Before price', 'quick-view-woo' ),
				'action'   => 'woocommerce_after_shop_loop_item_title',
				'priority' => 7,
			),
			'before-add-to-cart' => array(
				'label'    => __( 'Before Add to Cart button', 'quick-view-woo' ),
				'action'   => 'woocommerce_after_shop_loop_item',
				'priority' => 7,
			),
			'after-add-to-cart'  => array(
				'label'    => __( 'After Add to Cart button', 'quick-view-woo' ),
				'action'   => 'woocommerce_after_shop_loop_item',
				'priority' => 15,
			),
		) );

		if ( false !== $position ) {
			if ( array_key_exists( $position, $positions ) ) {
				return apply_filters( 'quickviewwoo_button_position_info', $positions[ $position ], $positions );
			} else {
				return apply_filters( 'quickviewwoo_button_position_info', $positions[ self::get_defaults( 'quickviewwoo_button_position' ) ], $positions );
			}
		}

		return $positions;
	}

	public static function get_button_position_labels() {
		return wp_list_pluck( self::get_button_position_info(), 'label' );
	}

	public static function get_options( $option = false ) {
		$options  = array();
		$defaults = self::get_defaults();

		foreach ( $defaults as $key => $default ) {
			$options[ $key ] = get_option( $key, $default );
		}

		if ( false === $option ) {
			return $options;
		}

		$value = false;

		if ( array_key_exists( $option, $options ) ) {
			$value = $options[ $option ];
		}

		return apply_filters( 'quickviewwoo_get_option', $value, $key, $defaults[ $key ] );
	}

	public function woo_add_quickview_button( $product_id = false ) {
		if ( ! $this->should_show_button() ) {
			return;
		}

		if ( empty( $product_id ) ) {
			$product_id = get_the_ID();
		}

		$position = self::get_options( 'quickviewwoo_button_position' );

		$classes = self::get_options( 'quickviewwoo_button_classes' );
		$classes = explode( ' ', $classes );
		$classes = apply_filters( 'quickviewwoo_button_classes', array_merge( array(
			'quickviewwoo-button',
			'quickviewwoo-button-js',
			"quickviewwoo-button-{$position}",
		), $classes ) );
		$classes = implode( ' ', $classes );

		$url = add_query_arg( array(
			'action' => 'quickviewwoo',
			'pid'    => $product_id,
		), admin_url( 'admin-ajax.php' ) );

		?>
		<span data-mfp-src="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $classes ); ?>">
			<?php echo esc_html( get_option( 'quickviewwoo_button_text', self::get_defaults( 'quickviewwoo_button_text' ) ) ); ?>
		</span>
		<?php
	}

	public function filter_forced_position_button_classes( $classes ) {
		$position   = self::get_options( 'quickviewwoo_button_position' );
		$find_class = "quickviewwoo-button-{$position}";
		$found      = array_search( $find_class, $classes, true );

		if ( false !== $found ) {
			unset( $classes[ $found ] );
		}

		return array_merge( $classes, array(
			'quickviewwoo-button-forced',
		) );
	}

	public function add_to_cart_form_action( $url ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $url;
		}

		if ( ! empty( $_REQUEST['action'] ) && 'quick-view-woo' === $_REQUEST['action'] ) {
			return '';
		}

		return $url;
	}

	public static function add_hidden_inputs() {
		$product = wc_get_product();
		?>
		<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>">
		<input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>">
		<?php
	}

	public static function get_star_font_css() {
		$fonts_path = untrailingslashit( WC()->plugin_url() ) . '/assets/fonts';

		ob_start();
		?>
		@font-face {
			font-family: star;
			src: url(<?php echo esc_url_raw( $fonts_path ); ?>/star.eot);
			src: url(<?php echo esc_url_raw( $fonts_path ); ?>/star.eot?#iefix) format("embedded-opentype"), url(<?php echo esc_url_raw( $fonts_path ); ?>/star.woff) format("woff"), url(<?php echo esc_url_raw( $fonts_path ); ?>/star.ttf) format("truetype"), url(<?php echo esc_url_raw( $fonts_path ); ?>/star.svg#star) format("svg");
			font-weight: 400;
			font-style: normal
		}
		<?php

		$css = ob_get_clean();

		return $css;
	}

	public function generate_css() {
		$options = self::get_options();

		$additional_css = array();

		if ( true === apply_filters( 'quickviewwoo_add_star_font_css', true ) ) {
			$additional_css['star_font'] = wp_kses( self::get_star_font_css(), 'strip' );
		}

		//
		// Modal
		//
		$this->css->add_rule( 'modal_container', '.quickviewwoo-product' );
		if ( $options['quickviewwoo_modal_container_width'] ) {
			$this->css->set_property( 'modal_container', 'max-width', intval( $options['quickviewwoo_modal_container_width'] ) . 'px' );
		}
		if ( $options['quickviewwoo_content_bg_color'] ) {
			$this->css->set_property( 'modal_container', 'background-color', sanitize_hex_color( $options['quickviewwoo_content_bg_color'] ) );
		}
		if ( $options['quickviewwoo_content_border_color'] ) {
			$this->css->set_property( 'modal_container', 'border-color', sanitize_hex_color( $options['quickviewwoo_content_border_color'] ) );
		}

		$this->css->add_rule( 'modal_container_text', '.quickviewwoo-product, .quickviewwoo-product h1, .quickviewwoo-product h2, .quickviewwoo-product h3, .quickviewwoo-product h4, .quickviewwoo-product h5, .quickviewwoo-product h6, .quickviewwoo-modal .mfp-close' );
		if ( $options['quickviewwoo_content_text_color'] ) {
			$this->css->set_property( 'modal_container_text', 'color', sanitize_hex_color( $options['quickviewwoo_content_text_color'] ) );
		}


		// Colors

		$this->css->add_rule( 'page_overlay', '.quickviewwoo-modal' );
		if ( $options['quickviewwoo_page_overlay_color'] ) {
			$this->css->set_property( 'page_overlay', 'background-color', sanitize_hex_color( $options['quickviewwoo_page_overlay_color'] ) );
		}

		$this->css->add_rule( 'content_link', '.quickviewwoo-product a' );
		if ( $options['quickviewwoo_content_link_color'] ) {
			$this->css->set_property( 'content_link', 'color', sanitize_hex_color( $options['quickviewwoo_content_link_color'] ) );
		}

		$this->css->add_rule( 'content_link_hover', '.quickviewwoo-product a:hover' );
		if ( $options['quickviewwoo_content_link_hover_color'] ) {
			$this->css->set_property( 'content_link_hover', 'color', sanitize_hex_color( $options['quickviewwoo_content_link_hover_color'] ) );
		}

		$this->css->add_rule( 'content_star_rating_inactive', '.quickviewwoo-product .quickviewwoo-star-rating::before' );
		if ( $options['quickviewwoo_content_rating_inactive_color'] ) {
			$this->css->set_property( 'content_star_rating_inactive', 'color', sanitize_hex_color( $options['quickviewwoo_content_rating_inactive_color'] ) );
			$this->css->set_property( 'content_star_rating_inactive', 'opacity', 1 );
		}

		$this->css->add_rule( 'content_star_rating_active', '.quickviewwoo-product .quickviewwoo-star-rating span::before' );
		if ( $options['quickviewwoo_content_rating_active_color'] ) {
			$this->css->set_property( 'content_star_rating_active', 'color', sanitize_hex_color( $options['quickviewwoo_content_rating_active_color'] ) );
		}

		$this->css->add_rule( 'content_button', '.quickviewwoo-product .btn, .quickviewwoo-product .button, .quickviewwoo-product input[type="submit"], .quickviewwoo-product input[type="reset"], .quickviewwoo-product button[type="submit"]' );
		if ( $options['quickviewwoo_content_button_bg_color'] ) {
			$this->css->set_property( 'content_button', 'background-color', sanitize_hex_color( $options['quickviewwoo_content_button_bg_color'] ) );
		}
		if ( $options['quickviewwoo_content_button_text_color'] ) {
			$this->css->set_property( 'content_button', 'color', sanitize_hex_color( $options['quickviewwoo_content_button_text_color'] ) );
		}
		if ( $options['quickviewwoo_content_button_border_color'] ) {
			$this->css->set_property( 'content_button', 'border-color', sanitize_hex_color( $options['quickviewwoo_content_button_border_color'] ) );
		}

		$this->css->add_rule( 'content_button_hover', '.quickviewwoo-product .btn:hover, .quickviewwoo-product .button:hover, .quickviewwoo-product input[type="submit"]:hover, .quickviewwoo-product input[type="reset"]:hover, .quickviewwoo-product button[type="submit"]:hover' );
		if ( $options['quickviewwoo_content_button_hover_bg_color'] ) {
			$this->css->set_property( 'content_button_hover', 'background-color', sanitize_hex_color( $options['quickviewwoo_content_button_hover_bg_color'] ) );
		}
		if ( $options['quickviewwoo_content_button_hover_text_color'] ) {
			$this->css->set_property( 'content_button_hover', 'color', sanitize_hex_color( $options['quickviewwoo_content_button_hover_text_color'] ) );
		}
		if ( $options['quickviewwoo_content_button_hover_border_color'] ) {
			$this->css->set_property( 'content_button_hover', 'border-color', sanitize_hex_color( $options['quickviewwoo_content_button_hover_border_color'] ) );
		}

		/**
		 * Hook: quickviewwoo_before_generate_css.
		 *
		 * @param QVW_CSS $css The QVW_CSS instance (passed by reference).
		 */
		do_action_ref_array( 'quickviewwoo_before_generate_css', array( &$this->css ) );

		$css = $this->css->generate_css();

		$additional_css = apply_filters( 'quickviewwoo_additional_css_rules', $additional_css );

		$all_css = array_merge( array( $css ), $additional_css );
		$all_css = implode( PHP_EOL, $all_css );

		return apply_filters( 'quickviewwoo_css', $all_css, $options );
	}

	private function load_theme_support() {
		if ( ! apply_filters( 'quickviewwoo_load_theme_support', true ) ) {
			return;
		}

		do_action( 'quickviewwoo_load_theme_support' );
	}

	public function plugin_activated() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		do_action( 'quickviewwoo_activated' );

		flush_rewrite_rules();
	}

	public function plugin_deactivated() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		do_action( 'quickviewwoo_deactivated' );

		flush_rewrite_rules();
	}

	public static function notice_woocommerce_required() {
		?>
		<div class="error notice">
			<p>
				<?php echo wp_kses(
					sprintf(
						/* translators: %s is a URL. */
						__( 'Quick View requires WooCommerce. Please install and activate the free <a href="%s" target="_blank">WooCommerce</a> plugin.', 'quick-view-woo' ),
						'https://wordpress.org/plugins/woocommerce/'
					), array( 'a' => array( 'href' => true, 'target' => true ) )
				); ?>
			</p>
		</div>
		<?php
	}

	public static function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	public function plugin_url() {
		return self::$plugin_url;
	}

	public function plugin_path() {
		return self::$plugin_path;
	}
}

/**
 * Displays the Quick View button.
 *
 * Helper function to manually display the Quick View button in custom theme templates.
 *
 * @param string $position Required. The button will only be outputted if this parameter matches the stored button position option, 'quickviewwoo_button_position'.
 *                         Pass 'forced' to ignore the stored option.
 *                         Valid values are:
 *                              'forced',
 *                              'before-thumbnail',
 *                              'before-title',
 *                              'before-rating',
 *                              'before-price',
 *                              'before-add-to-cart',
 *                              'after-add-to-cart'
 * @param int|false $product_id
 */
function quickviewwoo_show_button( $position, $product_id = false ) {
	$qv = QuickViewWoo();

	if ( 'forced' === $position ) {
		add_filter( 'quickviewwoo_button_classes', array( $qv, 'filter_forced_position_button_classes' ), 10 );
		$qv->enqueue_scripts();
		$qv->woo_add_quickview_button( $product_id );
		remove_filter( 'quickviewwoo_button_classes', array( $qv, 'filter_forced_position_button_classes' ), 10 );
	}

	if ( $position === $qv::get_options( 'quickviewwoo_button_position' ) ) {
		$qv->woo_add_quickview_button( $product_id );
	}
}

/**
 * Main instance of QuickViewWoo.
 *
 * Returns the working instance of QuickViewWoo. No need for globals.
 *
 * @since  1.0.0
 * @return QuickViewWoo
 */
function QuickViewWoo() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return QuickViewWoo::instance();
}

add_action( 'plugins_loaded', array( QuickViewWoo(), 'plugin_setup' ) );
register_activation_hook( __FILE__, array( QuickViewWoo(), 'plugin_activated' ) );
register_deactivation_hook( __FILE__, array( QuickViewWoo(), 'plugin_deactivated' ) );
