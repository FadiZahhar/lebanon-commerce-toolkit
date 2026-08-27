<?php
/**
 * District shipping method registration.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;
use ProSolutions\LebanonCommerceToolkit\Domain\Shipping\ShippingRateTable;

/**
 * Registers a zone-compatible WooCommerce shipping method.
 */
final class ShippingMethodRegistrar implements Service {
	/**
	 * Shared location repository for WooCommerce-instantiated method objects.
	 *
	 * @var LocationRepository|null
	 */
	private static $locations;

	/**
	 * Shared parser.
	 *
	 * @var ShippingRateTable|null
	 */
	private static $rate_table;

	/**
	 * Constructor.
	 *
	 * @param LocationRepository $locations  Locations.
	 * @param ShippingRateTable  $rate_table Rate parser.
	 */
	public function __construct( LocationRepository $locations, ShippingRateTable $rate_table ) {
		self::$locations  = $locations;
		self::$rate_table = $rate_table;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_shipping_init', array( $this, 'load_method' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_method' ) );
	}

	/**
	 * Trigger autoload after WC_Shipping_Method exists.
	 *
	 * @return void
	 */
	public function load_method() {
		class_exists( LebanonDistrictShippingMethod::class );
	}

	/**
	 * Add method to WooCommerce.
	 *
	 * @param array<string,string> $methods Methods.
	 * @return array<string,string>
	 */
	public function register_method( array $methods ) {
		$methods['lct_district_delivery'] = LebanonDistrictShippingMethod::class;
		return $methods;
	}

	/**
	 * Get locations dependency.
	 *
	 * @return LocationRepository
	 */
	public static function locations() {
		if ( ! self::$locations ) {
			self::$locations = new LocationRepository( LCT_PLUGIN_DIR . 'data/lebanon-locations.php' );
		}
		return self::$locations;
	}

	/**
	 * Get parser dependency.
	 *
	 * @return ShippingRateTable
	 */
	public static function rate_table() {
		if ( ! self::$rate_table ) {
			self::$rate_table = new ShippingRateTable();
		}
		return self::$rate_table;
	}
}
