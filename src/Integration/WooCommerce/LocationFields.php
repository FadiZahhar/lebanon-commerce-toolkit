<?php
/**
 * WooCommerce location fields integration.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Core\Options;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;
use Throwable;
use WC_Customer;
use WC_Order;
use WP_Error;
use WP_REST_Request;

/**
 * Adds Lebanon-specific address fields to classic and block checkouts.
 */
final class LocationFields implements Service {
	/**
	 * Additional checkout field ID.
	 *
	 * @var string
	 */
	const BLOCK_FIELD_ID = 'lebanon-commerce-toolkit/district';

	/**
	 * Locations.
	 *
	 * @var LocationRepository
	 */
	private $locations;

	/**
	 * Options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param LocationRepository $locations Locations.
	 * @param Options            $options   Options.
	 */
	public function __construct( LocationRepository $locations, Options $options ) {
		$this->locations = $locations;
		$this->options   = $options;
	}

	/**
	 * Register integration hooks.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! $this->options->enabled( 'enable_locations' ) ) {
			return;
		}

		add_filter( 'woocommerce_states', array( $this, 'add_governorates' ) );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'localize_address_fields' ) );
		add_filter( 'woocommerce_billing_fields', array( $this, 'add_billing_district' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_district' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_classic_checkout' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_classic_order_fields' ), 10, 2 );
		add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'save_classic_customer_fields' ), 10, 2 );
		add_action( 'woocommerce_after_save_address_validation', array( $this, 'validate_account_address' ), 10, 4 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'capture_classic_checkout_session' ) );

		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'add_address_format' ) );
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'add_order_billing_district' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'add_order_shipping_district' ), 10, 2 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'add_customer_district' ), 10, 3 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'replace_formatted_district' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_admin_billing_district' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'render_admin_shipping_district' ) );

		add_action( 'woocommerce_init', array( $this, 'register_block_checkout_field' ) );
		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->register_store_api_callback();
		} else {
			add_action( 'woocommerce_blocks_loaded', array( $this, 'register_store_api_callback' ) );
		}
		add_action( 'woocommerce_store_api_checkout_update_draft', array( $this, 'capture_store_api_request' ) );
		add_action( 'woocommerce_store_api_checkout_update_customer_from_request', array( $this, 'capture_store_api_customer_request' ), 10, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'sync_block_order_meta' ), 20, 2 );
		add_action( 'woocommerce_checkout_validate_order_before_payment', array( $this, 'validate_block_order_districts' ), 5, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ), 30 );
	}

	/**
	 * Add Lebanese governorates to WooCommerce states.
	 *
	 * @param array<string,array<string,string>> $states States.
	 * @return array<string,array<string,string>>
	 */
	public function add_governorates( array $states ) {
		$states['LB'] = $this->locations->governorate_options();
		return $states;
	}

	/**
	 * Adjust Lebanon address labels and requirements.
	 *
	 * @param array<string,mixed> $locale Country locale definitions.
	 * @return array<string,mixed>
	 */
	public function localize_address_fields( array $locale ) {
		if ( ! isset( $locale['LB'] ) ) {
			$locale['LB'] = array();
		}

		$locale['LB']['state'] = array(
			'label'    => __( 'Governorate', 'lebanon-commerce-toolkit' ),
			'required' => true,
			'priority' => 70,
		);
		$locale['LB']['city'] = array(
			'label'    => __( 'City / Area', 'lebanon-commerce-toolkit' ),
			'required' => true,
			'priority' => 80,
		);
		$locale['LB']['postcode'] = array(
			'label'    => __( 'Postal code (optional)', 'lebanon-commerce-toolkit' ),
			'required' => false,
			'hidden'   => false,
			'priority' => 90,
		);

		return $locale;
	}

	/**
	 * Add billing district field.
	 *
	 * @param array<string,mixed> $fields Fields.
	 * @return array<string,mixed>
	 */
	public function add_billing_district( array $fields ) {
		if ( $this->is_account_address_context() ) {
			return $fields;
		}

		$fields['billing_lct_district'] = $this->classic_field_definition( 'billing' );
		return $fields;
	}

	/**
	 * Add shipping district field.
	 *
	 * @param array<string,mixed> $fields Fields.
	 * @return array<string,mixed>
	 */
	public function add_shipping_district( array $fields ) {
		if ( $this->is_account_address_context() ) {
			return $fields;
		}

		$fields['shipping_lct_district'] = $this->classic_field_definition( 'shipping' );
		return $fields;
	}

	/**
	 * Validate district fields during classic checkout.
	 *
	 * @param array<string,mixed> $data   Posted checkout data.
	 * @param WP_Error            $errors Error collector.
	 * @return void
	 */
	public function validate_classic_checkout( $data, WP_Error $errors ) {
		$this->validate_classic_address( 'billing', (array) $data, $errors );

		if ( ! empty( $data['ship_to_different_address'] ) ) {
			$this->validate_classic_address( 'shipping', (array) $data, $errors );
		}
	}

	/**
	 * Save classic fields through the order CRUD API.
	 *
	 * @param WC_Order            $order Order.
	 * @param array<string,mixed> $data  Checkout data.
	 * @return void
	 */
	public function save_classic_order_fields( WC_Order $order, $data ) {
		$data     = is_array( $data ) ? $data : array();
		$billing  = $this->sanitize_district( isset( $data['billing_lct_district'] ) ? $data['billing_lct_district'] : '' );
		$shipping = $this->sanitize_district( isset( $data['shipping_lct_district'] ) ? $data['shipping_lct_district'] : '' );

		if ( empty( $data['ship_to_different_address'] ) && '' === $shipping ) {
			$shipping = $billing;
		}

		$this->set_order_district( $order, 'billing', $billing );
		$this->set_order_district( $order, 'shipping', $shipping );
	}

	/**
	 * Save classic fields to customer metadata.
	 *
	 * @param int                 $customer_id Customer ID.
	 * @param array<string,mixed> $data        Checkout data.
	 * @return void
	 */
	public function save_classic_customer_fields( $customer_id, $data ) {
		$data      = is_array( $data ) ? $data : array();
		$districts = array(
			'billing'  => $this->sanitize_district( isset( $data['billing_lct_district'] ) ? $data['billing_lct_district'] : '' ),
			'shipping' => $this->sanitize_district( isset( $data['shipping_lct_district'] ) ? $data['shipping_lct_district'] : '' ),
		);

		if ( empty( $data['ship_to_different_address'] ) && '' === $districts['shipping'] ) {
			$districts['shipping'] = $districts['billing'];
		}

		foreach ( $districts as $group => $district ) {
			$key      = $group . '_lct_district';
			$meta_key = $this->additional_field_meta_key( $group );

			if ( $district ) {
				update_user_meta( $customer_id, $meta_key, $district );
			} else {
				delete_user_meta( $customer_id, $meta_key );
			}

			// Remove the pre-release legacy key after a customer saves checkout.
			delete_user_meta( $customer_id, $key );
		}
	}

	/**
	 * Validate an address saved from My Account using the pending customer.
	 *
	 * @param int                 $user_id      Customer ID.
	 * @param string              $address_type billing or shipping.
	 * @param array<string,mixed> $address      Submitted core address fields.
	 * @param WC_Customer         $customer     Pending customer object.
	 * @return void
	 */
	public function validate_account_address( $user_id, $address_type, $address, WC_Customer $customer ) {
		unset( $user_id, $address );

		if ( ! in_array( $address_type, array( 'billing', 'shipping' ), true ) ) {
			return;
		}

		$country_getter = 'get_' . $address_type . '_country';
		$state_getter   = 'get_' . $address_type . '_state';
		$country         = is_callable( array( $customer, $country_getter ) ) ? $customer->{$country_getter}() : '';
		$governorate     = is_callable( array( $customer, $state_getter ) ) ? sanitize_key( $customer->{$state_getter}() ) : '';
		$district        = $this->get_additional_field_from_object( $customer, $address_type );
		$errors          = new WP_Error();

		$this->validate_location_values( $country, $governorate, $district, $errors );

		foreach ( $errors->get_error_messages() as $message ) {
			wc_add_notice( $message, 'error' );
		}
	}

	/**
	 * Keep the active classic checkout district in the WooCommerce session.
	 *
	 * @param string $post_data URL-encoded checkout payload.
	 * @return void
	 */
	public function capture_classic_checkout_session( $post_data ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$data = array();
		parse_str( (string) $post_data, $data );

		$use_shipping = ! empty( $data['ship_to_different_address'] ) && 'LB' === ( isset( $data['shipping_country'] ) ? $data['shipping_country'] : '' );
		$district_key = $use_shipping ? 'shipping_lct_district' : 'billing_lct_district';
		$district     = $this->sanitize_district( isset( $data[ $district_key ] ) ? $data[ $district_key ] : '' );

		WC()->session->set( 'lct_checkout_district', $district );
	}

	/**
	 * Add district placeholder to Lebanon addresses.
	 *
	 * @param array<string,string> $formats Address formats.
	 * @return array<string,string>
	 */
	public function add_address_format( array $formats ) {
		$format = isset( $formats['LB'] ) ? $formats['LB'] : ( isset( $formats['default'] ) ? $formats['default'] : "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}" );

		if ( false === strpos( $format, '{lct_district}' ) ) {
			if ( false !== strpos( $format, '{city}' ) ) {
				$format = str_replace( '{city}', "{city}\n{lct_district}", $format );
			} elseif ( false !== strpos( $format, '{state}' ) ) {
				$format = str_replace( '{state}', "{lct_district}\n{state}", $format );
			} else {
				$format .= "\n{lct_district}";
			}
		}

		$formats['LB'] = $format;
		return $formats;
	}

	/**
	 * Add district to an order billing address array.
	 *
	 * @param array<string,string> $address Address.
	 * @param WC_Order             $order   Order.
	 * @return array<string,string>
	 */
	public function add_order_billing_district( $address, WC_Order $order ) {
		$address['lct_district'] = $this->district_label_from_order( $order, 'billing' );
		return $address;
	}

	/**
	 * Add district to an order shipping address array.
	 *
	 * @param array<string,string> $address Address.
	 * @param WC_Order             $order   Order.
	 * @return array<string,string>
	 */
	public function add_order_shipping_district( $address, WC_Order $order ) {
		$address['lct_district'] = $this->district_label_from_order( $order, 'shipping' );
		return $address;
	}

	/**
	 * Add district to My Account address display.
	 *
	 * @param array<string,string> $address      Address.
	 * @param int                  $customer_id  Customer ID.
	 * @param string               $address_type Address type.
	 * @return array<string,string>
	 */
	public function add_customer_district( $address, $customer_id, $address_type ) {
		$district = $this->get_customer_district( $customer_id, $address_type );
		$address['lct_district'] = $this->locations->district_label( $district, false );
		return $address;
	}

	/**
	 * Replace the custom district token.
	 *
	 * @param array<string,string> $replacements Replacements.
	 * @param array<string,string> $address      Address.
	 * @return array<string,string>
	 */
	public function replace_formatted_district( $replacements, $address ) {
		$replacements['{lct_district}'] = isset( $address['lct_district'] ) ? $address['lct_district'] : '';
		return $replacements;
	}

	/**
	 * Render billing district in order admin.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function render_admin_billing_district( WC_Order $order ) {
		$this->render_admin_district( $order, 'billing' );
	}

	/**
	 * Render shipping district in order admin.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function render_admin_shipping_district( WC_Order $order ) {
		$this->render_admin_district( $order, 'shipping' );
	}

	/**
	 * Register official Checkout Block address field.
	 *
	 * @return void
	 */
	public function register_block_checkout_field() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		$options = array();

		foreach ( $this->locations->flattened_district_options() as $value => $label ) {
			$options[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		$country_is_lebanon = array(
			'customer' => array(
				'properties' => array(
					'address' => array(
						'properties' => array(
							'country' => array(
								'const' => 'LB',
							),
						),
					),
				),
			),
		);

		$country_is_not_lebanon = array(
			'customer' => array(
				'properties' => array(
					'address' => array(
						'properties' => array(
							'country' => array(
								'not' => array( 'const' => 'LB' ),
							),
						),
					),
				),
			),
		);

		$args = array(
			'id'                => self::BLOCK_FIELD_ID,
			'label'             => __( 'District', 'lebanon-commerce-toolkit' ),
			'optionalLabel'     => __( 'District (optional)', 'lebanon-commerce-toolkit' ),
			'placeholder'       => __( 'Select a district', 'lebanon-commerce-toolkit' ),
			'location'          => 'address',
			'type'              => 'select',
			'options'           => $options,
			'hidden'            => $country_is_not_lebanon,
			'sanitize_callback' => array( $this, 'sanitize_district' ),
			'validate_callback' => array( $this, 'validate_block_district' ),
		);

		if ( $this->options->enabled( 'require_district' ) ) {
			$args['required'] = $country_is_lebanon;
		}

		woocommerce_register_additional_checkout_field( $args );
	}

	/**
	 * Register on-demand cart update callback for Checkout Blocks.
	 *
	 * @return void
	 */
	public function register_store_api_callback() {
		if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			return;
		}

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'lebanon-commerce-toolkit',
				'callback'  => array( $this, 'update_store_api_session' ),
			)
		);
	}

	/**
	 * Update session from extensionCartUpdate data.
	 *
	 * @param array<string,mixed> $data Extension data.
	 * @return void
	 */
	public function update_store_api_session( $data ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$district = $this->sanitize_district( isset( $data['district'] ) ? $data['district'] : '' );
		WC()->session->set( 'lct_checkout_district', $district );

		if ( WC()->cart ) {
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Capture Checkout Block PATCH state.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return void
	 */
	public function capture_store_api_request( WP_REST_Request $request ) {
		$this->capture_district_from_request( $request );
	}

	/**
	 * Capture Checkout Block customer update state.
	 *
	 * @param \WC_Customer   $customer Customer.
	 * @param WP_REST_Request $request  Request.
	 * @return void
	 */
	public function capture_store_api_customer_request( $customer, WP_REST_Request $request ) {
		unset( $customer );
		$this->capture_district_from_request( $request );
	}

	/**
	 * Validate persisted Checkout Block district/state combinations.
	 *
	 * @param WC_Order $order  Order.
	 * @param WP_Error $errors Errors.
	 * @return void
	 */
	public function validate_block_order_districts( WC_Order $order, WP_Error $errors ) {
		$this->validate_order_address_district( $order, 'billing', $errors );

		$this->validate_order_address_district( $order, 'shipping', $errors );
	}

	/**
	 * Copy official block field values to stable plugin-owned order meta.
	 *
	 * @param WC_Order        $order   Order.
	 * @param WP_REST_Request $request Checkout request.
	 * @return void
	 */
	public function sync_block_order_meta( WC_Order $order, WP_REST_Request $request ) {
		unset( $request );
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			$this->set_order_district( $order, $group, $this->get_block_field_from_order( $order, $group ), false );
		}
	}

	/**
	 * Load checkout scripts only where relevant.
	 *
	 * @return void
	 */
	public function enqueue_checkout_assets() {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			wp_enqueue_style( 'lct-public' );
			wp_enqueue_script( 'lct-classic-checkout' );
			wp_enqueue_script( 'lct-block-checkout' );
			return;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			wp_enqueue_style( 'lct-public' );
		}
	}

	/**
	 * Validate a Block Checkout district.
	 *
	 * @param string $value Value.
	 * @return WP_Error|null
	 */
	public function validate_block_district( $value ) {
		if ( '' !== $value && ! $this->locations->is_valid_district( $value ) ) {
			return new WP_Error( 'lct_invalid_district', __( 'Please select a valid Lebanese district.', 'lebanon-commerce-toolkit' ) );
		}

		return null;
	}

	/**
	 * Sanitize a district key.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public function sanitize_district( $value ) {
		$value = $this->locations->normalize_district_key( sanitize_text_field( (string) $value ) );
		return $this->locations->is_valid_district( $value ) ? $value : '';
	}

	/**
	 * Build classic checkout field definition.
	 *
	 * @param string $type Address type.
	 * @return array<string,mixed>
	 */
	private function classic_field_definition( $type ) {
		$options = array( '' => __( 'Select a district', 'lebanon-commerce-toolkit' ) ) + $this->locations->flattened_district_options();
		$user_id = get_current_user_id();

		return array(
			'type'              => 'select',
			'label'             => __( 'District', 'lebanon-commerce-toolkit' ),
			'required'          => false,
			'class'             => array( 'form-row-wide', 'lct-district-field' ),
			'priority'          => 75,
			'options'           => $options,
			'default'           => $user_id ? $this->get_customer_district( $user_id, $type ) : '',
			'custom_attributes' => array( 'data-lct-checkout-district' => $type ),
		);
	}

	/**
	 * Determine whether WooCommerce is rendering a My Account address form.
	 *
	 * The official additional checkout field API owns that context. Avoiding the
	 * classic custom field here prevents two district fields from being rendered.
	 *
	 * @return bool
	 */
	private function is_account_address_context() {
		return function_exists( 'is_account_page' ) && is_account_page();
	}

	/**
	 * Return the canonical WooCommerce additional-field metadata key.
	 *
	 * @param string $group billing or shipping.
	 * @return string
	 */
	private function additional_field_meta_key( $group ) {
		if ( class_exists( CheckoutFields::class ) && is_callable( array( CheckoutFields::class, 'get_group_key' ) ) ) {
			return CheckoutFields::get_group_key( $group ) . self::BLOCK_FIELD_ID;
		}

		$prefix = 'shipping' === $group ? '_wc_shipping/' : '_wc_billing/';
		return $prefix . self::BLOCK_FIELD_ID;
	}

	/**
	 * Read a customer's canonical district with a pre-release legacy fallback.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param string $group       billing or shipping.
	 * @return string
	 */
	private function get_customer_district( $customer_id, $group ) {
		$district = get_user_meta( $customer_id, $this->additional_field_meta_key( $group ), true );

		if ( ! $district ) {
			$district = get_user_meta( $customer_id, $group . '_lct_district', true );
		}

		return $this->sanitize_district( $district );
	}

	/**
	 * Persist one order district in canonical and stable plugin-owned metadata.
	 *
	 * @param WC_Order $order            Order.
	 * @param string   $group            billing or shipping.
	 * @param string   $district         Sanitized district.
	 * @param bool     $write_canonical  Whether to write canonical Woo metadata.
	 * @return void
	 */
	private function set_order_district( WC_Order $order, $group, $district, $write_canonical = true ) {
		$district = $this->sanitize_district( $district );

		if ( $district ) {
			$order->update_meta_data( '_lct_' . $group . '_district', $district );

			if ( $write_canonical ) {
				$order->update_meta_data( $this->additional_field_meta_key( $group ), $district );
			}
			return;
		}

		$order->delete_meta_data( '_lct_' . $group . '_district' );

		if ( $write_canonical ) {
			$order->delete_meta_data( $this->additional_field_meta_key( $group ) );
		}
	}

	/**
	 * Validate one classic address.
	 *
	 * @param string              $type   billing or shipping.
	 * @param array<string,mixed> $data   Posted data.
	 * @param WP_Error            $errors Errors.
	 * @return void
	 */
	private function validate_classic_address( $type, array $data, WP_Error $errors ) {
		$country     = isset( $data[ $type . '_country' ] ) ? $data[ $type . '_country' ] : '';
		$governorate = isset( $data[ $type . '_state' ] ) ? sanitize_key( $data[ $type . '_state' ] ) : '';
		$district    = isset( $data[ $type . '_lct_district' ] ) ? $this->sanitize_district( $data[ $type . '_lct_district' ] ) : '';

		$this->validate_location_values( $country, $governorate, $district, $errors );
	}

	/**
	 * Validate one Lebanese country/governorate/district combination.
	 *
	 * @param string   $country     Country code.
	 * @param string   $governorate Governorate slug.
	 * @param string   $district    Canonical district key.
	 * @param WP_Error $errors      Error collector.
	 * @return void
	 */
	private function validate_location_values( $country, $governorate, $district, WP_Error $errors ) {
		if ( 'LB' !== $country ) {
			return;
		}

		if ( ! $this->locations->is_valid_governorate( $governorate ) ) {
			$errors->add(
				'lct_invalid_governorate',
				__( 'Please select a valid Lebanese governorate.', 'lebanon-commerce-toolkit' )
			);
			return;
		}

		if ( '' === $district && $this->options->enabled( 'require_district' ) ) {
			$errors->add(
				'lct_required_district',
				__( 'Please select a Lebanese district.', 'lebanon-commerce-toolkit' )
			);
			return;
		}

		if ( '' !== $district && $governorate !== $this->locations->governorate_from_district( $district ) ) {
			$errors->add(
				'lct_invalid_district',
				__( 'The selected district does not belong to the selected governorate.', 'lebanon-commerce-toolkit' )
			);
		}
	}

	/**
	 * Capture district from a Store API request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return void
	 */
	private function capture_district_from_request( WP_REST_Request $request ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$shipping = (array) $request->get_param( 'shipping_address' );
		$billing  = (array) $request->get_param( 'billing_address' );
		$address  = 'LB' === ( isset( $shipping['country'] ) ? $shipping['country'] : '' ) ? $shipping : $billing;
		$district = $this->sanitize_district( isset( $address[ self::BLOCK_FIELD_ID ] ) ? $address[ self::BLOCK_FIELD_ID ] : '' );

		WC()->session->set( 'lct_checkout_district', $district );
	}

	/**
	 * Validate one persisted order address.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $group  billing or shipping.
	 * @param WP_Error $errors Errors.
	 * @return void
	 */
	private function validate_order_address_district( WC_Order $order, $group, WP_Error $errors ) {
		$country_getter = 'get_' . $group . '_country';
		$state_getter   = 'get_' . $group . '_state';

		if ( ! is_callable( array( $order, $country_getter ) ) || 'LB' !== $order->{$country_getter}() ) {
			return;
		}

		$district = $this->get_block_field_from_order( $order, $group );
		$state    = is_callable( array( $order, $state_getter ) ) ? sanitize_key( $order->{$state_getter}() ) : '';

		$this->validate_location_values( 'LB', $state, $district, $errors );
	}

	/**
	 * Retrieve an official additional address field from an order.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $group billing or shipping.
	 * @return string
	 */
	private function get_block_field_from_order( WC_Order $order, $group ) {
		return $this->get_additional_field_from_object( $order, $group );
	}

	/**
	 * Retrieve an official additional address field from a WooCommerce object.
	 *
	 * @param WC_Order|WC_Customer $object Order or customer object.
	 * @param string               $group  billing or shipping.
	 * @return string
	 */
	private function get_additional_field_from_object( $object, $group ) {
		try {
			if ( ! class_exists( Package::class ) || ! class_exists( CheckoutFields::class ) ) {
				return '';
			}

			$checkout_fields = Package::container()->get( CheckoutFields::class );
			$value           = $checkout_fields->get_field_from_object( self::BLOCK_FIELD_ID, $object, $group );
			return $this->sanitize_district( $value );
		} catch ( Throwable $exception ) {
			unset( $exception );
			return '';
		}
	}

	/**
	 * Get a district label from either stable or official block metadata.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $group billing or shipping.
	 * @return string
	 */
	private function district_label_from_order( WC_Order $order, $group ) {
		$value = $order->get_meta( '_lct_' . $group . '_district', true );

		if ( ! $value ) {
			$value = $this->get_block_field_from_order( $order, $group );
		}

		return $this->locations->district_label( $value, false );
	}

	/**
	 * Render one district line in order admin.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $group Address group.
	 * @return void
	 */
	private function render_admin_district( WC_Order $order, $group ) {
		$label = $this->district_label_from_order( $order, $group );

		if ( '' === $label ) {
			return;
		}

		echo '<p><strong>' . esc_html__( 'District:', 'lebanon-commerce-toolkit' ) . '</strong> ' . esc_html( $label ) . '</p>';
	}
}
