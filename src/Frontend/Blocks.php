<?php
/**
 * Dynamic Gutenberg blocks.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Frontend;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Core\Options;
use ProSolutions\LebanonCommerceToolkit\Domain\Currency\CurrencyConverter;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;

/**
 * Registers server-rendered blocks with a dependency-free editor script.
 */
final class Blocks implements Service {
	/**
	 * Shortcode renderer reused by blocks.
	 *
	 * @var Shortcodes
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param LocationRepository $locations Locations.
	 * @param Options            $options   Options.
	 * @param CurrencyConverter  $currency  Converter.
	 */
	public function __construct( LocationRepository $locations, Options $options, CurrencyConverter $currency ) {
		$this->renderer = new Shortcodes( $locations, $options, $currency );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register dynamic blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		wp_register_script(
			'lct-blocks-editor',
			LCT_PLUGIN_URL . 'blocks/editor.js',
			array( 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-block-editor' ),
			LCT_VERSION,
			true
		);

		wp_set_script_translations(
			'lct-blocks-editor',
			'lebanon-commerce-toolkit',
			LCT_PLUGIN_DIR . 'languages'
		);

		register_block_type(
			LCT_PLUGIN_DIR . 'blocks/location-selector',
			array(
				'render_callback' => array( $this, 'render_location_block' ),
			)
		);

		register_block_type(
			LCT_PLUGIN_DIR . 'blocks/secondary-price',
			array(
				'render_callback' => array( $this, 'render_price_block' ),
			)
		);
	}

	/**
	 * Render location block.
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 * @return string
	 */
	public function render_location_block( $attributes ) {
		return $this->renderer->render_location_selector(
			array(
				'show_city' => ! empty( $attributes['showCity'] ) ? 'yes' : 'no',
				'required'  => ! empty( $attributes['required'] ) ? 'yes' : 'no',
			)
		);
	}

	/**
	 * Render secondary price block.
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 * @return string
	 */
	public function render_price_block( $attributes ) {
		return $this->renderer->render_secondary_price(
			array(
				'product_id' => isset( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0,
				'amount'     => isset( $attributes['amount'] ) ? $attributes['amount'] : '',
			)
		);
	}
}
