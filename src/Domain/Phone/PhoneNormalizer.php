<?php
/**
 * Lebanese phone normalization.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Domain\Phone;

/**
 * Normalizes common Lebanese number formats without hard-coding operator ranges.
 */
final class PhoneNormalizer {
	/**
	 * Normalize a phone number.
	 *
	 * Local seven/eight-digit numbers are converted to +961 format. Numbers with
	 * a different explicit country code are preserved in normalized international
	 * format so overseas customers are not rejected.
	 *
	 * @param string $number          Raw number.
	 * @param bool   $assume_lebanon Whether local numbers should assume +961.
	 * @return string
	 */
	public function normalize( $number, $assume_lebanon = true ) {
		$number = $this->ascii_digits( trim( (string) $number ) );

		if ( '' === $number ) {
			return '';
		}

		$has_plus = 0 === strpos( $number, '+' );
		$digits   = preg_replace( '/\D+/', '', $number );

		if ( ! is_string( $digits ) || '' === $digits ) {
			return '';
		}

		if ( 0 === strpos( $digits, '00' ) ) {
			$digits   = substr( $digits, 2 );
			$has_plus = true;
		}

		if ( 0 === strpos( $digits, '961' ) ) {
			$national = ltrim( substr( $digits, 3 ), '0' );
			return '+961' . $national;
		}

		if ( $has_plus ) {
			return '+' . $digits;
		}

		if ( $assume_lebanon ) {
			$national = ltrim( $digits, '0' );

			if ( 7 === strlen( $national ) || 8 === strlen( $national ) ) {
				return '+961' . $national;
			}
		}

		return $digits;
	}

	/**
	 * Check a Lebanese number using a deliberately broad structural rule.
	 *
	 * @param string $number Raw or normalized number.
	 * @return bool
	 */
	public function is_valid_lebanon( $number ) {
		$normalized = $this->normalize( $number, true );
		return 1 === preg_match( '/^\+961[1-9][0-9]{6,7}$/', $normalized );
	}

	/**
	 * Convert Arabic and Persian numerals to ASCII.
	 *
	 * @param string $value Input.
	 * @return string
	 */
	private function ascii_digits( $value ) {
		return strtr(
			$value,
			array(
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
			)
		);
	}
}
