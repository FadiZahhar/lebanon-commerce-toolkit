<?php
/**
 * WooCommerce phone integration.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Integration\WooCommerce;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Core\Options;
use ProSolutions\LebanonCommerceToolkit\Domain\Phone\PhoneNormalizer;
use WC_Customer;
use WC_Order;
use WP_Error;
use WP_REST_Request;

/**
 * Normalizes and validates Lebanese checkout phone numbers.
 */
final class PhoneFields implements Service {
	/**
	 * Normalizer.
	 *
	 * @var PhoneNormalizer
	 */
	private $phone;

	/**
	 * Options.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param PhoneNormalizer $phone   Normalizer.
	 * @param Options         $options Options.
	 */
	public function __construct( PhoneNormalizer $phone, Options $options ) {
		$this->phone   = $phone;
		$this->options = $options;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! $this->options->enabled( 'enable_phone' ) ) {
			return;
		}

		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'normalize_classic_posted_data' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_classic_phone' ), 20, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'normalize_order_phone' ), 20 );
		add_filter( 'woocommerce_process_myaccount_field_billing_phone', array( $this, 'normalize_account_phone' ) );
		add_action( 'woocommerce_after_save_address_validation', array( $this, 'validate_account_phone' ), 20, 4 );
		add_action( 'woocommerce_store_api_checkout_update_customer_from_request', array( $this, 'normalize_store_api_customer' ), 20, 2 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'normalize_store_api_order_phone' ), 20, 2 );
		add_action( 'woocommerce_checkout_validate_order_before_payment', array( $this, 'validate_block_order_phone' ), 10, 2 );
	}

	/**
	 * Normalize classic checkout payload.
	 *
	 * @param array<string,mixed> $data Posted data.
	 * @return array<string,mixed>
	 */
	public function normalize_classic_posted_data( $data ) {
		if ( isset( $data['billing_phone'] ) ) {
			$data['billing_phone'] = $this->phone->normalize( $data['billing_phone'], 'LB' === ( isset( $data['billing_country'] ) ? $data['billing_country'] : '' ) );
		}

		return $data;
	}

	/**
	 * Validate classic Lebanese billing phone.
	 *
	 * @param array<string,mixed> $data   Posted data.
	 * @param WP_Error            $errors Errors.
	 * @return void
	 */
	public function validate_classic_phone( $data, WP_Error $errors ) {
		if ( 'off' === $this->options->get( 'phone_validation' ) || 'LB' !== ( isset( $data['billing_country'] ) ? $data['billing_country'] : '' ) ) {
			return;
		}

		$number = isset( $data['billing_phone'] ) ? $data['billing_phone'] : '';

		if ( '' !== $number && ! $this->is_valid_lebanon( $number ) ) {
			$errors->add( 'lct_invalid_phone', __( 'Please enter a valid Lebanese phone number, for example +961 3 123 456.', 'lebanon-commerce-toolkit' ) );
		}
	}

	/**
	 * Normalize the order's billing phone through CRUD setters.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function normalize_order_phone( WC_Order $order ) {
		$assume_lebanon = 'LB' === $order->get_billing_country();
		$normalized     = $this->phone->normalize( $order->get_billing_phone(), $assume_lebanon );

		if ( $normalized ) {
			$order->set_billing_phone( $normalized );
		}
	}

	/**
	 * Normalize My Account billing phone.
	 *
	 * @param string $value Phone.
	 * @return string
	 */
	public function normalize_account_phone( $value ) {
		$country = isset( $_POST['billing_country'] ) ? wc_clean( wp_unslash( $_POST['billing_country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies this form nonce.
		return $this->phone->normalize( $value, 'LB' === $country );
	}

	/**
	 * Validate a Lebanese billing number when My Account saves an address.
	 *
	 * @param int          $user_id      User ID.
	 * @param string       $address_type Address type.
	 * @param array        $address      Submitted address values.
	 * @param WC_Customer  $customer     Customer object.
	 * @return void
	 */
	public function validate_account_phone( $user_id, $address_type, $address, WC_Customer $customer ) {
		unset( $user_id, $address );

		if ( 'billing' !== $address_type || 'off' === $this->options->get( 'phone_validation' ) ) {
			return;
		}

		$country = $customer->get_billing_country();
		$number  = $customer->get_billing_phone();

		if ( 'LB' === $country && '' !== $number && ! $this->is_valid_lebanon( $number ) ) {
			wc_add_notice( __( 'Please enter a valid Lebanese phone number, for example +961 3 123 456.', 'lebanon-commerce-toolkit' ), 'error' );
		}
	}

	/**
	 * Normalize Store API customer data before it is saved.
	 *
	 * @param WC_Customer     $customer Customer.
	 * @param WP_REST_Request $request  Request.
	 * @return void
	 */
	public function normalize_store_api_customer( WC_Customer $customer, WP_REST_Request $request ) {
		$billing = (array) $request->get_param( 'billing_address' );

		if ( empty( $billing['phone'] ) ) {
			return;
		}

		$normalized = $this->phone->normalize( $billing['phone'], 'LB' === ( isset( $billing['country'] ) ? $billing['country'] : '' ) );

		if ( $normalized ) {
			$customer->set_billing_phone( $normalized );
		}
	}

	/**
	 * Normalize the Store API order after request fields have been applied.
	 *
	 * @param WC_Order        $order   Order.
	 * @param WP_REST_Request $request Checkout request.
	 * @return void
	 */
	public function normalize_store_api_order_phone( WC_Order $order, WP_REST_Request $request ) {
		unset( $request );
		$this->normalize_order_phone( $order );
	}

	/**
	 * Validate the persisted Block Checkout order before payment.
	 *
	 * @param WC_Order $order  Order.
	 * @param WP_Error $errors Errors.
	 * @return void
	 */
	public function validate_block_order_phone( WC_Order $order, WP_Error $errors ) {
		if ( 'off' === $this->options->get( 'phone_validation' ) || 'LB' !== $order->get_billing_country() ) {
			return;
		}

		$number = $order->get_billing_phone();

		if ( '' !== $number && ! $this->is_valid_lebanon( $number ) ) {
			$errors->add( 'lct_invalid_phone', __( 'Please enter a valid Lebanese phone number, for example +961 3 123 456.', 'lebanon-commerce-toolkit' ) );
		}
	}

	/**
	 * Apply the public validation filter.
	 *
	 * @param string $number Number.
	 * @return bool
	 */
	private function is_valid_lebanon( $number ) {
		/**
		 * Filter Lebanese phone validation.
		 *
		 * @param bool   $is_valid Default structural result.
		 * @param string $number   Submitted number.
		 */
		return (bool) apply_filters( 'lct_is_valid_lebanon_phone', $this->phone->is_valid_lebanon( $number ), $number );
	}
}
