<?php
/**
 * Front-end shortcodes.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Frontend;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Core\Options;
use ProSolutions\LebanonCommerceToolkit\Domain\Currency\CurrencyConverter;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;

/**
 * Provides no-build theme-independent UI primitives.
 */
final class Shortcodes implements Service {
	/**
	 * Locations.
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
	 * Converter.
	 *
	 * @var CurrencyConverter
	 */
	private $currency;

	/**
	 * Constructor.
	 *
	 * @param LocationRepository $locations Locations.
	 * @param Options            $options   Options.
	 * @param CurrencyConverter  $currency  Converter.
	 */
	public function __construct( LocationRepository $locations, Options $options, CurrencyConverter $currency ) {
		$this->locations = $locations;
		$this->options   = $options;
		$this->currency  = $currency;
	}

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'lct_location_selector', array( $this, 'render_location_selector' ) );
		add_shortcode( 'lct_secondary_price', array( $this, 'render_secondary_price' ) );
	}

	/**
	 * Render the location selector.
	 *
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @return string
	 */
	public function render_location_selector( $attributes = array() ) {
		$attributes = shortcode_atts(
			array(
				'name_prefix' => 'lct',
				'governorate' => '',
				'district'    => '',
				'city'        => '',
				'show_city'   => 'yes',
				'required'    => 'no',
			),
			(array) $attributes,
			'lct_location_selector'
		);

		wp_enqueue_style( 'lct-public' );
		wp_enqueue_script( 'lct-location-selector' );

		$instance_id         = wp_unique_id( 'lct-location-' );
		$prefix              = sanitize_key( $attributes['name_prefix'] );
		$prefix              = $prefix ? $prefix : 'lct';
		$required            = 'yes' === strtolower( (string) $attributes['required'] );
		$show_city           = 'yes' === strtolower( (string) $attributes['show_city'] );
		$selected_governorate = sanitize_key( $attributes['governorate'] );
		$selected_district    = $this->locations->normalize_district_key( $attributes['district'] );

		if ( ! $this->locations->is_valid_governorate( $selected_governorate ) ) {
			$selected_governorate = '';
		}

		if ( ! $this->locations->is_valid_district( $selected_district ) ) {
			$selected_district = '';
		}

		ob_start();
		?>
		<div class="lct-location-selector" data-lct-location-selector>
			<div class="lct-field">
				<label for="<?php echo esc_attr( $instance_id . '-governorate' ); ?>"><?php esc_html_e( 'Governorate', 'lebanon-commerce-toolkit' ); ?></label>
				<select id="<?php echo esc_attr( $instance_id . '-governorate' ); ?>" name="<?php echo esc_attr( $prefix . '_governorate' ); ?>" data-lct-governorate <?php required( $required ); ?>>
					<option value=""><?php esc_html_e( 'Select a governorate', 'lebanon-commerce-toolkit' ); ?></option>
					<?php foreach ( $this->locations->governorate_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_governorate, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="lct-field">
				<label for="<?php echo esc_attr( $instance_id . '-district' ); ?>"><?php esc_html_e( 'District', 'lebanon-commerce-toolkit' ); ?></label>
				<select id="<?php echo esc_attr( $instance_id . '-district' ); ?>" name="<?php echo esc_attr( $prefix . '_district' ); ?>" data-lct-district data-selected="<?php echo esc_attr( $selected_district ); ?>" <?php required( $required ); ?>>
					<option value=""><?php esc_html_e( 'Select a district', 'lebanon-commerce-toolkit' ); ?></option>
				</select>
			</div>
			<?php if ( $show_city ) : ?>
				<div class="lct-field">
					<label for="<?php echo esc_attr( $instance_id . '-city' ); ?>"><?php esc_html_e( 'City / Area', 'lebanon-commerce-toolkit' ); ?></label>
					<input id="<?php echo esc_attr( $instance_id . '-city' ); ?>" type="text" name="<?php echo esc_attr( $prefix . '_city' ); ?>" value="<?php echo esc_attr( $attributes['city'] ); ?>" <?php required( $required ); ?>>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render a secondary amount for a product or explicit amount.
	 *
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @return string
	 */
	public function render_secondary_price( $attributes = array() ) {
		if ( ! $this->options->enabled( 'enable_secondary_currency' ) ) {
			return '';
		}

		$attributes = shortcode_atts(
			array(
				'product_id' => '0',
				'amount'     => '',
				'prefix'     => '≈',
			),
			(array) $attributes,
			'lct_secondary_price'
		);

		if ( '' !== $attributes['amount'] ) {
			if ( ! is_numeric( $attributes['amount'] ) || (float) $attributes['amount'] < 0 ) {
				return '';
			}

			return $this->secondary_price_html( (float) $attributes['amount'], $attributes['prefix'] );
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product_id = absint( $attributes['product_id'] );

		if ( ! $product_id && function_exists( 'get_the_ID' ) ) {
			$product_id = get_the_ID();
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return '';
		}

		if ( $product->is_type( 'variable' ) ) {
			return $this->secondary_price_range_html(
				(float) $product->get_variation_price( 'min', true ),
				(float) $product->get_variation_price( 'max', true ),
				$attributes['prefix']
			);
		}

		if ( '' === $product->get_price() ) {
			return '';
		}

		return $this->secondary_price_html(
			(float) wc_get_price_to_display( $product ),
			$attributes['prefix']
		);
	}

	/**
	 * Create safe secondary price HTML.
	 *
	 * @param float  $amount Base amount.
	 * @param string $prefix Prefix.
	 * @return string
	 */
	public function secondary_price_html( $amount, $prefix = '≈' ) {
		$converted = $this->convert_amount( $amount );

		if ( null === $converted ) {
			return '';
		}

		$html = $this->secondary_price_wrapper( $prefix . ' ' . $this->format_amount( $converted ) );

		/**
		 * Filter the rendered secondary price HTML.
		 *
		 * @param string $html      HTML.
		 * @param float  $converted Converted amount.
		 * @param float  $amount    Base amount.
		 */
		return apply_filters( 'lct_secondary_currency_html', $html, $converted, (float) $amount );
	}

	/**
	 * Create safe secondary price range HTML.
	 *
	 * @param float  $minimum Minimum base amount.
	 * @param float  $maximum Maximum base amount.
	 * @param string $prefix  Prefix.
	 * @return string
	 */
	public function secondary_price_range_html( $minimum, $maximum, $prefix = '≈' ) {
		$converted_minimum = $this->convert_amount( $minimum );
		$converted_maximum = $this->convert_amount( $maximum );

		if ( null === $converted_minimum || null === $converted_maximum ) {
			return '';
		}

		if ( $converted_minimum === $converted_maximum ) {
			return $this->secondary_price_html( $minimum, $prefix );
		}

		$html = $this->secondary_price_wrapper(
			$prefix . ' ' . $this->format_amount( $converted_minimum ) . ' – ' . $this->format_amount( $converted_maximum )
		);

		/**
		 * Filter the rendered secondary price range HTML.
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
			(float) $minimum,
			(float) $maximum
		);
	}

	/**
	 * Convert a base amount.
	 *
	 * @param float $amount Base amount.
	 * @return float|null
	 */
	private function convert_amount( $amount ) {
		$rate = (float) $this->options->get( 'exchange_rate', 0 );

		if ( $rate <= 0 || (float) $amount < 0 ) {
			return null;
		}

		return $this->currency->convert(
			(float) $amount,
			$rate,
			(float) $this->options->get( 'rounding_increment', 0 )
		);
	}

	/**
	 * Format a converted amount.
	 *
	 * @param float $converted Converted amount.
	 * @return string
	 */
	private function format_amount( $converted ) {
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
	private function secondary_price_wrapper( $text ) {
		$code = strtoupper( sanitize_key( (string) $this->options->get( 'secondary_currency_code', 'LBP' ) ) );

		return '<span class="lct-secondary-price" data-currency="' . esc_attr( $code ) . '" aria-label="' .
			esc_attr__( 'Approximate secondary currency amount', 'lebanon-commerce-toolkit' ) . '">' .
			esc_html( $text ) . '</span>';
	}
}
