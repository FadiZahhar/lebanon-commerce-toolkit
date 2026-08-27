<?php
/**
 * Lebanon district shipping method.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce;

use WC_Shipping_Method;

/**
 * Zone-scoped, merchant-configurable Lebanon delivery method.
 */
final class LebanonDistrictShippingMethod extends WC_Shipping_Method {
	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping method instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'lct_district_delivery';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Lebanon District Delivery', 'lebanon-commerce-toolkit' );
		$this->method_description = __( 'Set delivery prices by Lebanese district, governorate fallback, or one Lebanon-wide fallback.', 'lebanon-commerce-toolkit' );
		$this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );

		$this->init();
	}

	/**
	 * Initialize settings and save hook.
	 *
	 * @return void
	 */
	public function init() {
		$this->init_instance_form_fields();
		$this->init_settings();
		$this->enabled    = $this->get_option( 'enabled', 'yes' );
		$this->title      = $this->get_option( 'title', __( 'Local delivery', 'lebanon-commerce-toolkit' ) );
		$this->tax_status = $this->get_option( 'tax_status', 'taxable' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Configure zone instance fields.
	 *
	 * @return void
	 */
	public function init_instance_form_fields() {
		$this->instance_form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable', 'lebanon-commerce-toolkit' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this shipping method', 'lebanon-commerce-toolkit' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Checkout title', 'lebanon-commerce-toolkit' ),
				'type'        => 'text',
				'default'     => __( 'Local delivery', 'lebanon-commerce-toolkit' ),
				'desc_tip'    => true,
				'description' => __( 'Shown to customers at checkout.', 'lebanon-commerce-toolkit' ),
			),
			'tax_status' => array(
				'title'   => __( 'Tax status', 'lebanon-commerce-toolkit' ),
				'type'    => 'select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'lebanon-commerce-toolkit' ),
					'none'    => _x( 'None', 'Tax status', 'lebanon-commerce-toolkit' ),
				),
			),
			'rate_table' => array(
				'title'       => __( 'District rates', 'lebanon-commerce-toolkit' ),
				'type'        => 'textarea',
				'default'     => '',
				'css'         => 'min-height:180px;font-family:monospace;',
				'description' => __( 'One rule per line in the store currency. District: keserwan-jbeil:keserwan=4.00. Governorate fallback: @keserwan-jbeil=5.00. Lebanon-wide fallback: *=7.00.', 'lebanon-commerce-toolkit' ),
			),
			'fallback_cost' => array(
				'title'       => __( 'Method fallback cost', 'lebanon-commerce-toolkit' ),
				'type'        => 'price',
				'default'     => '',
				'description' => __( 'Used only when no district, governorate, or * rule matches. Leave empty to hide this method when no rule matches.', 'lebanon-commerce-toolkit' ),
			),
			'free_shipping_minimum' => array(
				'title'       => __( 'Free shipping minimum', 'lebanon-commerce-toolkit' ),
				'type'        => 'price',
				'default'     => '',
				'description' => __( 'Optional cart contents subtotal in the store currency. Leave empty to disable.', 'lebanon-commerce-toolkit' ),
			),
		);
	}

	/**
	 * Restrict method to Lebanon destinations.
	 *
	 * @param array<string,mixed> $package Shipping package.
	 * @return bool
	 */
	public function is_available( $package ) {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		$country = isset( $package['destination']['country'] ) ? $package['destination']['country'] : '';
		return 'LB' === $country && parent::is_available( $package );
	}

	/**
	 * Calculate the matching rate.
	 *
	 * @param array<string,mixed> $package Shipping package.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		if ( ! $this->is_available( $package ) ) {
			return;
		}

		$locations   = ShippingMethodRegistrar::locations();
		$rate_table  = ShippingMethodRegistrar::rate_table();
		$governorate = isset( $package['destination']['state'] ) ? sanitize_key( $package['destination']['state'] ) : '';
		$district    = '';

		if ( function_exists( 'WC' ) && WC()->session ) {
			$district = strtolower( sanitize_text_field( (string) WC()->session->get( 'lct_checkout_district', '' ) ) );
		}

		if ( ! $locations->is_valid_district( $district ) || ( $governorate && $governorate !== $locations->governorate_from_district( $district ) ) ) {
			$district = '';
		}

		$rules          = $rate_table->parse( (string) $this->get_option( 'rate_table', '' ) );
		$fallback_value = $this->get_option( 'fallback_cost', '' );
		$fallback       = $this->non_negative_decimal_or_null( $fallback_value );
		$cost           = $rate_table->resolve( $rules, $district, $governorate, $fallback );

		if ( null === $cost ) {
			return;
		}

		$free_minimum = $this->non_negative_decimal_or_null(
			$this->get_option( 'free_shipping_minimum', '' )
		);
		$contents_cost = isset( $package['contents_cost'] )
			? max( 0.0, (float) $package['contents_cost'] )
			: 0.0;

		if ( null !== $free_minimum && $contents_cost >= $free_minimum ) {
			$cost = 0.0;
		}

		$this->add_rate(
			array(
				'id'      => $this->get_rate_id(),
				'label'   => $this->title,
				'cost'    => $cost,
				'package' => $package,
			)
		);
	}

	/**
	 * Validate and normalize rate table input.
	 *
	 * @param string $key   Field key.
	 * @param string $value Field value.
	 * @return string
	 */
	public function validate_rate_table_field( $key, $value ) {
		unset( $key );
		$parsed = ShippingMethodRegistrar::rate_table()->parse( wp_unslash( (string) $value ) );
		$lines  = array();

		$locations = ShippingMethodRegistrar::locations();

		foreach ( $parsed as $rule => $cost ) {
			if ( '*' === $rule ) {
				$lines[ $rule ] = $rule . '=' . wc_format_decimal( $cost );
				continue;
			}

			if ( 0 === strpos( $rule, '@' ) ) {
				$governorate = sanitize_key( substr( $rule, 1 ) );

				if ( $locations->is_valid_governorate( $governorate ) ) {
					$key           = '@' . $governorate;
					$lines[ $key ] = $key . '=' . wc_format_decimal( $cost );
				}
				continue;
			}

			$district = $locations->normalize_district_key( $rule );

			if ( $locations->is_valid_district( $district ) ) {
				$lines[ $district ] = $district . '=' . wc_format_decimal( $cost );
			}
		}

		return implode( "\n", array_values( $lines ) );
	}

	/**
	 * Convert a merchant setting to a non-negative decimal or null.
	 *
	 * @param mixed $value Raw setting.
	 * @return float|null
	 */
	private function non_negative_decimal_or_null( $value ) {
		if ( '' === $value || null === $value ) {
			return null;
		}

		$decimal = wc_format_decimal( $value );

		if ( '' === $decimal || ! is_numeric( $decimal ) || (float) $decimal < 0 ) {
			return null;
		}

		return (float) $decimal;
	}
}
