<?php
/**
 * PHPUnit bootstrap for framework-independent domain tests.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.valueFound
		unset( $hook_name );
		return $value;
	}
}

if ( ! function_exists( 'determine_locale' ) ) {
	function determine_locale() {
		return 'en_US';
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( (string) $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( (float) $number, (int) $decimals, '.', ',' );
	}
}

require_once dirname( __DIR__ ) . '/src/Domain/Locations/LocationRepository.php';
require_once dirname( __DIR__ ) . '/src/Domain/Phone/PhoneNormalizer.php';
require_once dirname( __DIR__ ) . '/src/Domain/Currency/CurrencyConverter.php';
require_once dirname( __DIR__ ) . '/src/Domain/Shipping/ShippingRateTable.php';
