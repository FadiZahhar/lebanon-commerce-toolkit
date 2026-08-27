<?php
/**
 * Runtime requirements.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Core;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;

/**
 * Prevents integrations from booting on unsupported environments.
 */
final class Requirements implements Service {
	/**
	 * Minimum WooCommerce version.
	 *
	 * @var string
	 */
	const MIN_WC_VERSION = '9.9.0';

	/**
	 * Register requirement notices.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! $this->is_satisfied() ) {
			add_action( 'admin_notices', array( $this, 'render_notice' ) );
		}
	}

	/**
	 * Check requirements.
	 *
	 * @return bool
	 */
	public function is_satisfied() {
		return class_exists( 'WooCommerce' )
			&& defined( 'WC_VERSION' )
			&& version_compare( WC_VERSION, self::MIN_WC_VERSION, '>=' );
	}

	/**
	 * Render an actionable admin notice.
	 *
	 * @return void
	 */
	public function render_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			$message = __( 'Lebanon Commerce Toolkit requires WooCommerce to be installed and active.', 'lebanon-commerce-toolkit' );
		} else {
			$message = sprintf(
				/* translators: %s: minimum WooCommerce version. */
				esc_html__( 'Lebanon Commerce Toolkit requires WooCommerce %s or newer.', 'lebanon-commerce-toolkit' ),
				esc_html( self::MIN_WC_VERSION )
			);
		}
		?>
		<div class="notice notice-error"><p><?php echo wp_kses_post( $message ); ?></p></div>
		<?php
	}
}
