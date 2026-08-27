<?php
/**
 * Secondary currency conversion.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Domain\Currency;

/**
 * Pure conversion and formatting logic.
 */
final class CurrencyConverter {
	/**
	 * Convert and round an amount.
	 *
	 * @param float $amount             Base amount.
	 * @param float $exchange_rate      Manual exchange rate.
	 * @param float $rounding_increment Nearest increment; zero disables rounding.
	 * @return float
	 */
	public function convert( $amount, $exchange_rate, $rounding_increment = 0.0 ) {
		$converted = (float) $amount * (float) $exchange_rate;
		$increment = abs( (float) $rounding_increment );

		if ( $increment > 0 ) {
			$converted = round( $converted / $increment ) * $increment;
		}

		return $converted;
	}

	/**
	 * Format a converted amount.
	 *
	 * @param float  $amount   Amount.
	 * @param string $symbol   Currency symbol or code.
	 * @param int    $decimals Decimal places.
	 * @return string
	 */
	public function format( $amount, $symbol = 'LBP', $decimals = 0 ) {
		$formatted = function_exists( 'number_format_i18n' )
			? number_format_i18n( (float) $amount, (int) $decimals )
			: number_format( (float) $amount, (int) $decimals, '.', ',' );

		return trim( $formatted . ' ' . $symbol );
	}
}
