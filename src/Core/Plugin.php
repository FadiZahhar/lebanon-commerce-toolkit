<?php
/**
 * Plugin service registry.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Core;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;

/**
 * Coordinates the plugin's independent services.
 */
final class Plugin {
	/**
	 * Registered services.
	 *
	 * @var Service[]
	 */
	private $services;

	/**
	 * Constructor.
	 *
	 * @param Service[] $services Services to register.
	 */
	public function __construct( array $services ) {
		$this->services = $services;
	}

	/**
	 * Register every service.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( $this->services as $service ) {
			$service->register();
		}
	}
}
