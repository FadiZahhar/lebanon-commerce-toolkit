<?php
/**
 * District shipping rate table parser.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Domain\Shipping;

/**
 * Parses merchant-entered shipping rules into a deterministic map.
 */
final class ShippingRateTable {
	/**
	 * Parse rules.
	 *
	 * Supported keys:
	 * - keserwan-jbeil:keserwan=4.00 (district)
	 * - @keserwan-jbeil=5.00 (governorate fallback)
	 * - *=7.00 (global fallback)
	 *
	 * @param string $raw Raw textarea value.
	 * @return array<string,float>
	 */
	public function parse( $raw ) {
		$rates = array();
		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );

		if ( ! is_array( $lines ) ) {
			return $rates;
		}

		foreach ( $lines as $line ) {
			$line = trim( preg_replace( '/\s+#.*$/', '', trim( $line ) ) );

			if ( '' === $line || 0 === strpos( $line, '#' ) || false === strpos( $line, '=' ) ) {
				continue;
			}

			list( $key, $value ) = array_map( 'trim', explode( '=', $line, 2 ) );
			$key                 = strtolower( $key );
			$value               = str_replace( ',', '.', $value );

			if ( ! preg_match( '/^(\*|@[a-z0-9-]+|[a-z0-9-]+:[a-z0-9-]+)$/', $key ) ) {
				continue;
			}

			if ( ! is_numeric( $value ) || (float) $value < 0 ) {
				continue;
			}

			$rates[ $key ] = (float) $value;
		}

		return $rates;
	}

	/**
	 * Resolve a rate using district, governorate, global, then method fallback.
	 *
	 * @param array<string,float> $rates          Parsed rates.
	 * @param string              $district_key   Composite district key.
	 * @param string              $governorate    Governorate slug.
	 * @param float|null          $method_fallback Method fallback.
	 * @return float|null
	 */
	public function resolve( array $rates, $district_key, $governorate, $method_fallback = null ) {
		if ( '' !== $district_key && isset( $rates[ $district_key ] ) ) {
			return $rates[ $district_key ];
		}

		$governorate_key = '@' . strtolower( trim( (string) $governorate ) );

		if ( '@' !== $governorate_key && isset( $rates[ $governorate_key ] ) ) {
			return $rates[ $governorate_key ];
		}

		if ( isset( $rates['*'] ) ) {
			return $rates['*'];
		}

		return null === $method_fallback ? null : (float) $method_fallback;
	}
}
