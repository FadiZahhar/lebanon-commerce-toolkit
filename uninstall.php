<?php
/**
 * Plugin uninstall routine.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete plugin options for the current site only when explicitly requested.
 *
 * Order and customer metadata is intentionally retained because it is part of
 * the store's business records and address history.
 *
 * @return void
 */
function lct_delete_current_site_options() {
	$options = get_option( 'lct_settings', array() );

	if ( 'yes' !== ( isset( $options['delete_data_on_uninstall'] ) ? $options['delete_data_on_uninstall'] : 'no' ) ) {
		return;
	}

	delete_option( 'lct_settings' );
	delete_option( 'lct_version' );
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		lct_delete_current_site_options();
		restore_current_blog();
	}
} else {
	lct_delete_current_site_options();
}
