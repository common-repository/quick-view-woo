<?php
/**
 * QuickViewWoo Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'QVW_Settings_QuickViewWoo', false ) ) {
	return new QVW_Settings_QuickViewWoo();
}

/**
 * QVW_Settings_QuickViewWoo.
 */
class QVW_Settings_QuickViewWoo extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'quick-view-woo';
		$this->label = __( 'Quick View', 'quick-view-woo' );

		parent::__construct();

		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize' ), 10, 3 );
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''      => __( 'Button', 'woocommerce' ),
			'modal' => __( 'Modal', 'woocommerce' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );

		if ( $current_section ) {
			do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
		}
	}

	public function sanitize( $value, $option, $raw_value ) {
		if ( empty( $option['id'] ) ) {
			return $value;
		}

		if ( 'quickviewwoo_button_classes' === $option['id'] ) {
			$value = trim( $raw_value );
			$value = explode( ' ', $value );
			$value = array_map( 'sanitize_html_class', $value );
			$value = array_map( 'trim', $value );
			$value = array_filter( $value );
			$value = implode( ' ', $value );
		}

		return $value;
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section name.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		if ( 'modal' === $current_section ) {
			$settings = apply_filters( 'quickviewwoo_modal_settings', array_merge(
				array(
					array(
						'title' => __( 'Dimensions', 'quick-view-woo' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'quickviewwoo_modal_dimensions_options',
					),
				),
				apply_filters( 'quickviewwoo_modal_settings_dimensions', array(
					array(
						'title'             => __( 'Modal width', 'quick-view-woo' ),
						'desc'              => __( 'Width of the popup window (in pixels). Only applies to non-mobile devices.', 'quick-view-woo' ),
						'id'                => 'quickviewwoo_modal_container_width',
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => 100,
							'step' => 1,
						),
						'default'           => QuickViewWoo::get_defaults( 'quickviewwoo_modal_container_width' ),
						'css'               => 'width:5em;',
					),
				) ),
				array(
					array(
						'type' => 'sectionend',
						'id'   => 'quickviewwoo_modal_dimensions_options',
					),
				),
				apply_filters( 'quickviewwoo_modal_settings_content', array() ),
				array(
					array(
						'title' => __( 'Colors', 'quick-view-woo' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'quickviewwoo_modal_color_options',
					),
					array(
						'title'    => __( 'Page Overlay color', 'quick-view-woo' ),
						'desc'     => sprintf( __( 'The color that covers the page\'s content when the popup appears.', 'quick-view-woo' ) ),
						'id'       => 'quickviewwoo_page_overlay_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_page_overlay_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Content Background color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_bg_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_bg_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Content Border color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_border_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_border_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Content Text color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_text_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_text_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Content Link color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_link_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_link_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Content Link Hover color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_link_hover_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_link_hover_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Content Rating Active color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_rating_active_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_rating_active_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Content Rating Inactive color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_rating_inactive_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_rating_inactive_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Buttons Background color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_button_bg_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_button_bg_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Buttons Text color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_button_text_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_button_text_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Buttons Border color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_button_border_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_button_border_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Buttons Hover Background color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_button_hover_bg_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_button_hover_bg_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Buttons Hover Text color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_button_hover_text_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_button_hover_text_color' ),
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Buttons Hover Border color', 'quick-view-woo' ),
						'id'       => 'quickviewwoo_content_button_hover_border_color',
						'type'     => 'color',
						'css'      => 'width:6em;',
						'default'  => QuickViewWoo::get_defaults( 'quickviewwoo_content_button_hover_border_color' ),
						'desc_tip' => true,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'quickviewwoo_modal_color_options',
					),

				)
			) );
		} else {
			$settings = apply_filters( 'quickviewwoo_button_settings', array_merge(
				array(
					array(
						'title' => __( 'Button', 'quick-view-woo' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'quickviewwoo_button_options',
					),

					array(
						'title'           => __( 'Visibility', 'quick-view-woo' ),
						'desc'            => __( 'Show button', 'quick-view-woo' ),
						'id'              => 'quickviewwoo_button_show',
						'default'         => QuickViewWoo::get_defaults( 'quickviewwoo_button_show' ),
						'type'            => 'checkbox',
						'checkboxgroup'   => 'start',
						'show_if_checked' => 'option',
					),
					array(
						'desc'            => __( 'Show button in mobile devices', 'quick-view-woo' ),
						'id'              => 'quickviewwoo_button_show_mobile',
						'default'         => QuickViewWoo::get_defaults( 'quickviewwoo_button_show_mobile' ),
						'type'            => 'checkbox',
						'checkboxgroup'   => '',
						'show_if_checked' => 'yes',
					),
					array(
						'desc'            => __( 'Show button in single products', 'quick-view-woo' ),
						'id'              => 'quickviewwoo_button_show_single',
						'default'         => QuickViewWoo::get_defaults( 'quickviewwoo_button_show_single' ),
						'type'            => 'checkbox',
						'checkboxgroup'   => '',
						'show_if_checked' => 'yes',
						'desc_tip'        => __( 'Applies to Related Products, Up-Sells, etc.', 'quick-view-woo' ),
					),
					array(
						'desc'            => __( 'Maximize non-shop compatibility', 'quick-view-woo' ),
						'id'              => 'quickviewwoo_button_max_compat',
						'default'         => QuickViewWoo::get_defaults( 'quickviewwoo_button_max_compat' ),
						'type'            => 'checkbox',
						'checkboxgroup'   => 'end',
						'show_if_checked' => 'yes',
						'desc_tip'        => __( 'Enabling this option, allows the Quick View buttons to be displayed in non-shop pages, such as on your homepage and pages built with page builders (Elementor, Divi, etc).', 'quick-view-woo' ),
					),


					array(
						'title'   => __( 'Button text', 'quick-view-woo' ),
						'id'      => 'quickviewwoo_button_text',
						'type'    => 'text',
						'default' => QuickViewWoo::get_defaults( 'quickviewwoo_button_text' ),
					),
					array(
						'title'   => __( 'Button classes', 'quick-view-woo' ),
						/* translators: %s is a list of space-separated html classes. */
						'desc'    => sprintf( __( 'Space-separated list of HTML classes that the Quick View button will have. Default: <code>%s</code>.', 'quick-view-woo' ), QuickViewWoo::get_defaults( 'quickviewwoo_button_classes' ) ),
						'id'      => 'quickviewwoo_button_classes',
						'type'    => 'text',
						'default' => QuickViewWoo::get_defaults( 'quickviewwoo_button_classes' ),
					),
					array(
						'title'   => __( 'Button position', 'quick-view-woo' ),
						'id'      => 'quickviewwoo_button_position',
						'type'    => 'select',
						'class'   => 'wc-enhanced-select',
						'css'     => 'min-width: 350px;',
						'default' => QuickViewWoo::get_defaults( 'quickviewwoo_button_position' ),
						'options' => QuickViewWoo::get_button_position_labels(),
					),
				),
				apply_filters( 'quickviewwoo_button_settings_categories', array() ),
				array(
					array(
						'type' => 'sectionend',
						'id'   => 'quickviewwoo_button_options',
					),

				),
				apply_filters( 'quickviewwoo_button_settings_colors', array() )
			) );
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}


}

return new QVW_Settings_QuickViewWoo();
