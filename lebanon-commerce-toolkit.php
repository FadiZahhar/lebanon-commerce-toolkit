<?php
/**
 * Plugin Name:       Lebanon Commerce Toolkit for WooCommerce
 * Plugin URI:        https://pro-solutions.net/
 * Description:       Lebanese checkout locations, phone normalization, secondary currency display, and district-based shipping for WooCommerce.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Pro-Solutions.net
 * Author URI:        https://pro-solutions.net/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lebanon-commerce-toolkit
 * Domain Path:       /languages
 * WC requires at least: 9.9
 * WC tested up to:   11.0.1
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

defined( 'ABSPATH' ) || exit;

define( 'LCT_VERSION', '0.1.0' );
define( 'LCT_PLUGIN_FILE', __FILE__ );
define( 'LCT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LCT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LCT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Lightweight PSR-4-style autoloader used by the distributable package.
 * Composer is only required for development tooling.
 */
spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'ProSolutions\\LebanonCommerceToolkit\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $prefix ) );
		$file           = LCT_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'ProSolutions\\LebanonCommerceToolkit\\Core\\Activator', 'activate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
);

add_action(
	'plugins_loaded',
	static function () {
		$requirements = new ProSolutions\LebanonCommerceToolkit\Core\Requirements();
		$requirements->register();

		if ( ! $requirements->is_satisfied() ) {
			return;
		}

		$options    = new ProSolutions\LebanonCommerceToolkit\Core\Options();
		$locations  = new ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository( LCT_PLUGIN_DIR . 'data/lebanon-locations.php' );
		$phone      = new ProSolutions\LebanonCommerceToolkit\Domain\Phone\PhoneNormalizer();
		$currency   = new ProSolutions\LebanonCommerceToolkit\Domain\Currency\CurrencyConverter();
		$rate_table = new ProSolutions\LebanonCommerceToolkit\Domain\Shipping\ShippingRateTable();

		$plugin = new ProSolutions\LebanonCommerceToolkit\Core\Plugin(
			array(
				new ProSolutions\LebanonCommerceToolkit\Core\Upgrader(),
				new ProSolutions\LebanonCommerceToolkit\Admin\Settings( $options, $locations ),
				new ProSolutions\LebanonCommerceToolkit\Api\LocationsController( $locations ),
				new ProSolutions\LebanonCommerceToolkit\Frontend\PublicAssets( $locations, $options ),
				new ProSolutions\LebanonCommerceToolkit\Frontend\Shortcodes( $locations, $options, $currency ),
				new ProSolutions\LebanonCommerceToolkit\Frontend\Blocks( $locations, $options, $currency ),
				new ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce\LocationFields( $locations, $options ),
				new ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce\PhoneFields( $phone, $options ),
				new ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce\DualCurrency( $currency, $options ),
				new ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce\ShippingMethodRegistrar( $locations, $rate_table ),
			)
		);

		$plugin->register();
	}
);
