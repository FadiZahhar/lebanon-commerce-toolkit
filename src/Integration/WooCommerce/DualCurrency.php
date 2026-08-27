<?php
/**
 * WooCommerce secondary currency display.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Core\Options;
use ProSolutions\LebanonCommerceToolkit\Domain\Currency\CurrencyConverter;

/**
 * Appends a clearly informational converted amount to WooCommerce prices.
 */
final class DualCurrency implements Service {
	/**
	 * Converter.
	 *
	 * @var CurrencyConverter
	 */
	private $currency;

	/**
	 * Options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param CurrencyConverter $currency Converter.
	 * @param Options           $options  Options.
	 */
	public function __construct( CurrencyConverter $currency, Options $options ) {
		$this->currency = $currency;
		$this->options  = $options;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! $this->options->enabled( 'enable_secondary_currency' ) ) {
			return;
		}

		if ( $this->options->enabled( 'show_secondary_product' ) ) {
			add_filter( 'woocommerce_get_price_html', array( $this, 'append_product_price' ), 30, 2 );
		}

		if ( $this->options->enabled( 'show_secondary_cart' ) ) {
			add_filter( 'woocommerce_cart_item_price', array( $this, 'append_cart_item_price' ), 30, 3 );
		}
	}

	/**
	 * Append to product price HTML.
	 *
	 * @param string      $html    Existing HTML.
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function append_product_price( $html, $product ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $html;
		}

		if ( false !== strpos( $html, 'lct-secondary-price' ) || ! $product ) {
			return $html;
		}

		if ( $product->is_type( 'variable' ) ) {
			$minimum = (float) $product->get_variation_price( 'min', true );
			$maximum = (float) $product->get_variation_price( 'max', true );

			if ( '' === $product->get_price() && 0.0 === $maximum ) {
				return $html;
			}

			return $html . $this->render_range( $minimum, $maximum );
		}

		if ( '' === $product->get_price() ) {
			return $html;
		}

		return $html . $this->render( (float) wc_get_price_to_display( $product ) );
	}

	/**
	 * Append to classic cart item unit price.
	 *
	 * @param string              $html          Existing HTML.
	 * @param array<string,mixed> $cart_item     Cart item.
	 * @param string              $cart_item_key Cart item key.
	 * @return string
	 */
	public function append_cart_item_price( $html, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		if ( false !== strpos( $html, 'lct-secondary-price' ) || empty( $cart_item['data'] ) ) {
			return $html;
		}

		$amount = (float) wc_get_price_to_display( $cart_item['data'] );
		return $html . $this->render( $amount );
	}

	/**
	 * Render one converted amount.
	 *
	 * @param float $amount Base amount.
	 * @return string
	 */
	private function render( $amount ) {
		$converted = $this->convert( $amount );

		if ( null === $converted ) {
			return '';
		}

		$formatted = $this->format( $converted );
		$html      = $this->wrapper( '≈ ' . $formatted );

		/**
		 * Filter the product/cart secondary price HTML.
		 *
		 * @param string $html      HTML.
		 * @param float  $converted Converted amount.
		 * @param float  $amount    Base amount.
		 */
		return apply_filters( 'lct_secondary_currency_html', $html, $converted, $amount );
	}

	/**
	 * Render a converted variable-product range.
	 *
	 * @param float $minimum Minimum base amount.
	 * @param float $maximum Maximum base amount.
	 * @return string
	 */
	private function render_range( $minimum, $maximum ) {
		$converted_minimum = $this->convert( $minimum );
		$converted_maximum = $this->convert( $maximum );

		if ( null === $converted_minimum || null === $converted_maximum ) {
			return '';
		}

		if ( $converted_minimum === $converted_maximum ) {
			return $this->render( $minimum );
		}

		$html = $this->wrapper(
			'≈ ' . $this->format( $converted_minimum ) . ' – ' . $this->format( $converted_maximum )
		);

		/**
		 * Filter variable-product secondary currency range markup.
		 *
		 * @param string $html              HTML.
		 * @param float  $converted_minimum Converted minimum.
		 * @param float  $converted_maximum Converted maximum.
		 * @param float  $minimum           Base minimum.
		 * @param float  $maximum           Base maximum.
		 */
		return apply_filters(
			'lct_secondary_currency_range_html',
			$html,
			$converted_minimum,
			$converted_maximum,
			$minimum,
			$maximum
		);
	}

	/**
	 * Convert an amount using merchant settings.
	 *
	 * @param float $amount Base amount.
	 * @return float|null
	 */
	private function convert( $amount ) {
		$rate = (float) $this->options->get( 'exchange_rate', 0 );

		if ( $rate <= 0 ) {
			return null;
		}

		return $this->currency->convert(
			max( 0.0, (float) $amount ),
			$rate,
			(float) $this->options->get( 'rounding_increment', 0 )
		);
	}

	/**
	 * Format a converted value.
	 *
	 * @param float $converted Converted amount.
	 * @return string
	 */
	private function format( $converted ) {
		return $this->currency->format(
			$converted,
			(string) $this->options->get( 'secondary_currency_symbol', 'LBP' ),
			0
		);
	}

	/**
	 * Build accessible theme-independent markup.
	 *
	 * @param string $text Display text.
	 * @return string
	 */
	private function wrapper( $text ) {
		$code = strtoupper( sanitize_key( (string) $this->options->get( 'secondary_currency_code', 'LBP' ) ) );

		return '<span class="lct-secondary-price" data-currency="' . esc_attr( $code ) . '" aria-label="' .
			esc_attr__( 'Approximate secondary currency amount', 'lebanon-commerce-toolkit' ) . '">' .
			esc_html( $text ) . '</span>';
	}
}
