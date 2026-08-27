<?php
/**
 * Front-end assets.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Frontend;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Core\Options;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;

/**
 * Registers theme-agnostic CSS and JavaScript handles.
 */
final class PublicAssets implements Service {
	/**
	 * Repository.
	 *
	 * @var LocationRepository
	 */
	private $locations;

	/**
	 * Options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param LocationRepository $locations Repository.
	 * @param Options            $options   Options.
	 */
	public function __construct( LocationRepository $locations, Options $options ) {
		$this->locations = $locations;
		$this->options   = $options;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
	}

	/**
	 * Register reusable assets without forcing them onto every page.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'lct-public', LCT_PLUGIN_URL . 'assets/css/public.css', array(), LCT_VERSION );

		wp_register_script(
			'lct-location-selector',
			LCT_PLUGIN_URL . 'assets/js/location-selector.js',
			array(),
			LCT_VERSION,
			true
		);

		wp_register_script(
			'lct-classic-checkout',
			LCT_PLUGIN_URL . 'assets/js/classic-checkout.js',
			array( 'jquery' ),
			LCT_VERSION,
			true
		);

		wp_register_script(
			'lct-block-checkout',
			LCT_PLUGIN_URL . 'assets/js/block-checkout.js',
			array( 'wp-data' ),
			LCT_VERSION,
			true
		);

		$data = array(
			'country'      => 'LB',
			'districts'    => $this->locations->javascript_map(),
			'placeholder'  => __( 'Select a district', 'lebanon-commerce-toolkit' ),
			'fieldId'      => 'lebanon-commerce-toolkit/district',
			'namespace'    => 'lebanon-commerce-toolkit',
			'requireField' => $this->options->enabled( 'require_district' ),
		);

		wp_localize_script( 'lct-location-selector', 'lctLocationData', $data );
		wp_localize_script( 'lct-classic-checkout', 'lctLocationData', $data );
		wp_localize_script( 'lct-block-checkout', 'lctLocationData', $data );

		if ( $this->current_page_uses_public_ui() ) {
			wp_enqueue_style( 'lct-public' );
		}
	}
	/**
	 * Detect shortcodes/blocks early enough to print CSS in the document head.
	 *
	 * @return bool
	 */
	private function current_page_uses_public_ui() {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		if (
			$this->options->enabled( 'enable_secondary_currency' )
			&& $this->options->enabled( 'show_secondary_product' )
			&& (
				( function_exists( 'is_product' ) && is_product() )
				|| ( function_exists( 'is_shop' ) && is_shop() )
				|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
			)
		) {
			return true;
		}

		if (
			$this->options->enabled( 'enable_secondary_currency' )
			&& $this->options->enabled( 'show_secondary_cart' )
			&& function_exists( 'is_cart' )
			&& is_cart()
		) {
			return true;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		$content = (string) $post->post_content;

		return has_shortcode( $content, 'lct_location_selector' )
			|| has_shortcode( $content, 'lct_secondary_price' )
			|| has_block( 'lebanon-commerce-toolkit/location-selector', $post )
			|| has_block( 'lebanon-commerce-toolkit/secondary-price', $post );
	}

}
