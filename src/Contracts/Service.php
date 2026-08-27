<?php
/**
 * Service contract.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Contracts;

/**
 * A plugin service that registers hooks, routes, blocks, or integrations.
 */
interface Service {
	/**
	 * Register the service with WordPress.
	 *
	 * @return void
	 */
	public function register();
}
