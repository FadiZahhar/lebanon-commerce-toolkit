<?php
/**
 * Plugin data upgrades.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Core;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;

/**
 * Runs small, idempotent upgrade routines when the plugin version changes.
 */
final class Upgrader implements Service {
	/**
	 * Register upgrade execution.
	 *
	 * @return void
	 */
	public function register() {
		$this->maybe_upgrade();
	}

	/**
	 * Apply migrations needed between the installed and current versions.
	 *
	 * @return void
	 */
	private function maybe_upgrade() {
		$installed_version = (string) get_option( 'lct_version', '0.0.0' );

		if ( defined( 'LCT_VERSION' ) && version_compare( $installed_version, LCT_VERSION, '>=' ) ) {
			return;
		}

		// Ensure settings introduced by future releases receive safe defaults.
		$stored = get_option( Options::OPTION_KEY, array() );
		$stored = is_array( $stored ) ? $stored : array();
		update_option( Options::OPTION_KEY, wp_parse_args( $stored, Options::defaults() ), false );

		update_option( 'lct_version', defined( 'LCT_VERSION' ) ? LCT_VERSION : '0.1.0', false );

		/**
		 * Fires after Lebanon Commerce Toolkit has completed an upgrade.
		 *
		 * @param string $installed_version Previously installed version.
		 * @param string $current_version   Current plugin version.
		 */
		do_action(
			'lct_upgraded',
			$installed_version,
			defined( 'LCT_VERSION' ) ? LCT_VERSION : '0.1.0'
		);
	}
}
