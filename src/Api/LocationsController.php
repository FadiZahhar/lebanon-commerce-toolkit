<?php
/**
 * Public locations REST API.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Api;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;
use WP_REST_Request;

/**
 * Exposes the bundled non-personal reference data.
 */
final class LocationsController implements Service {
	/**
	 * Repository.
	 *
	 * @var LocationRepository
	 */
	private $locations;

	/**
	 * Constructor.
	 *
	 * @param LocationRepository $locations Repository.
	 */
	public function __construct( LocationRepository $locations ) {
		$this->locations = $locations;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'lct/v1',
			'/locations',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_locations' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'locale' => array(
						'description'       => __( 'Response language.', 'lebanon-commerce-toolkit' ),
						'type'              => 'string',
						'enum'              => array( 'en', 'ar' ),
						'default'           => 'en',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Return location data.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_locations( WP_REST_Request $request ) {
		$locale       = 'ar' === $request->get_param( 'locale' ) ? 'ar' : 'en';
		$governorates = array();

		foreach ( $this->locations->governorate_options( $locale ) as $governorate_slug => $governorate_name ) {
			$districts = array();

			foreach ( $this->locations->district_options_for_governorate( $governorate_slug, $locale ) as $key => $district_name ) {
				$districts[] = array(
					'id'   => $key,
					'name' => $district_name,
				);
			}

			$governorates[] = array(
				'id'        => $governorate_slug,
				'name'      => $governorate_name,
				'districts' => $districts,
			);
		}

		$data = $this->locations->all();

		return rest_ensure_response(
			array(
				'country'      => 'LB',
				'locale'       => $locale,
				'data_version' => isset( $data['version'] ) ? $data['version'] : '',
				'governorates' => $governorates,
			)
		);
	}
}
