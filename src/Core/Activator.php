<?php
/**
 * Activation tasks.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Core;

/**
 * Performs safe, idempotent activation work.
 */
final class Activator {
	/**
	 * Activate the plugin.
	 *
	 * @param bool $network_wide Whether activation is network-wide.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::activate_site();
				restore_current_blog();
			}

			return;
		}

		self::activate_site();
	}

	/**
	 * Activate one site.
	 *
	 * @return void
	 */
	private static function activate_site() {
		if ( false === get_option( Options::OPTION_KEY, false ) ) {
			add_option( Options::OPTION_KEY, Options::defaults(), '', false );
		}

		update_option( 'lct_version', defined( 'LCT_VERSION' ) ? LCT_VERSION : '0.1.0', false );
	}
}
