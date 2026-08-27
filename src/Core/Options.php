<?php
/**
 * Plugin options access.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Core;

/**
 * Provides one typed access point for plugin settings.
 */
final class Options {
	/**
	 * WordPress option key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'lct_settings';

	/**
	 * Default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'enable_locations'          => 'yes',
			'require_district'          => 'yes',
			'enable_phone'              => 'yes',
			'phone_validation'          => 'relaxed',
			'enable_secondary_currency' => 'no',
			'secondary_currency_code'   => 'LBP',
			'secondary_currency_symbol' => 'LBP',
			'exchange_rate'             => '',
			'rounding_increment'        => '1000',
			'show_secondary_product'    => 'yes',
			'show_secondary_cart'       => 'no',
			'delete_data_on_uninstall'  => 'no',
		);
	}

	/**
	 * Return all settings with defaults applied.
	 *
	 * @return array<string,mixed>
	 */
	public function all() {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Return one setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$options = $this->all();
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Check a yes/no setting.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public function enabled( $key ) {
		return 'yes' === $this->get( $key, 'no' );
	}
}
