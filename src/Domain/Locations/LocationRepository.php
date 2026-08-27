<?php
/**
 * Lebanon locations repository.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Domain\Locations;

use RuntimeException;

/**
 * Reads and validates the packaged administrative dataset.
 */
final class LocationRepository {
	/**
	 * Dataset file.
	 *
	 * @var string
	 */
	private $data_file;

	/**
	 * Loaded dataset.
	 *
	 * @var array<string,mixed>|null
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param string $data_file Dataset file path.
	 */
	public function __construct( $data_file ) {
		$this->data_file = $data_file;
	}

	/**
	 * Return the complete filterable dataset.
	 *
	 * @return array<string,mixed>
	 */
	public function all() {
		if ( null === $this->data ) {
			if ( ! is_readable( $this->data_file ) ) {
				throw new RuntimeException( 'Lebanon location dataset is not readable.' );
			}

			$data = require $this->data_file;

			if ( ! is_array( $data ) || empty( $data['governorates'] ) ) {
				throw new RuntimeException( 'Lebanon location dataset is invalid.' );
			}

			$this->data = $data;
		}

		/**
		 * Filter the Lebanon administrative dataset.
		 *
		 * @param array<string,mixed> $data Dataset.
		 */
		return apply_filters( 'lct_location_data', $this->data );
	}

	/**
	 * Return localized governorate labels keyed by slug.
	 *
	 * @param string|null $locale Locale override.
	 * @return array<string,string>
	 */
	public function governorate_options( $locale = null ) {
		$options = array();

		foreach ( $this->all()['governorates'] as $slug => $governorate ) {
			$options[ $slug ] = $this->localized_name( $governorate['name'], $locale );
		}

		return $options;
	}

	/**
	 * Return localized district options for one governorate.
	 *
	 * Values use a stable governorate:district composite key.
	 *
	 * @param string      $governorate_slug Governorate slug.
	 * @param string|null $locale            Locale override.
	 * @return array<string,string>
	 */
	public function district_options_for_governorate( $governorate_slug, $locale = null ) {
		$governorate_slug = sanitize_key( $governorate_slug );
		$data              = $this->all();

		if ( empty( $data['governorates'][ $governorate_slug ]['districts'] ) ) {
			return array();
		}

		$options = array();

		foreach ( $data['governorates'][ $governorate_slug ]['districts'] as $district_slug => $district_name ) {
			$key             = $this->compose_key( $governorate_slug, $district_slug );
			$options[ $key ] = $this->localized_name( $district_name, $locale );
		}

		return $options;
	}

	/**
	 * Return all district options, including the governorate in each label.
	 *
	 * @param string|null $locale Locale override.
	 * @return array<string,string>
	 */
	public function flattened_district_options( $locale = null ) {
		$options      = array();
		$governorates = $this->governorate_options( $locale );

		foreach ( $governorates as $governorate_slug => $governorate_name ) {
			$districts = $this->district_options_for_governorate( $governorate_slug, $locale );

			foreach ( $districts as $key => $district_name ) {
				$options[ $key ] = sprintf( '%1$s — %2$s', $governorate_name, $district_name );
			}
		}

		return $options;
	}

	/**
	 * Return a serializable state-to-district map for front-end scripts.
	 *
	 * @param string|null $locale Locale override.
	 * @return array<string,array<int,array<string,string>>>
	 */
	public function javascript_map( $locale = null ) {
		$map = array();

		foreach ( $this->governorate_options( $locale ) as $governorate_slug => $label ) {
			unset( $label );
			$map[ $governorate_slug ] = array();

			$districts = $this->district_options_for_governorate( $governorate_slug, $locale );

			foreach ( $districts as $value => $district_label ) {
				$map[ $governorate_slug ][] = array(
					'value' => $value,
					'label' => $district_label,
				);
			}
		}

		return $map;
	}

	/**
	 * Check a governorate slug.
	 *
	 * @param string $slug Governorate slug.
	 * @return bool
	 */
	public function is_valid_governorate( $slug ) {
		$data = $this->all();
		return isset( $data['governorates'][ sanitize_key( $slug ) ] );
	}

	/**
	 * Convert a current or legacy district key to its canonical key.
	 *
	 * @param string $composite_key Current or legacy district key.
	 * @return string
	 */
	public function normalize_district_key( $composite_key ) {
		$parts = $this->split_key( $composite_key );

		if ( 2 !== count( $parts ) ) {
			return '';
		}

		$key  = $this->compose_key( $parts[0], $parts[1] );
		$data = $this->all();

		if ( ! empty( $data['district_aliases'][ $key ] ) ) {
			$alias_parts = $this->split_key( $data['district_aliases'][ $key ] );

			if ( 2 === count( $alias_parts ) ) {
				$key = $this->compose_key( $alias_parts[0], $alias_parts[1] );
			}
		}

		return $key;
	}

	/**
	 * Check a composite district key.
	 *
	 * @param string $composite_key Composite district key.
	 * @return bool
	 */
	public function is_valid_district( $composite_key ) {
		$key   = $this->normalize_district_key( $composite_key );
		$parts = $this->split_key( $key );

		if ( 2 !== count( $parts ) ) {
			return false;
		}

		$data = $this->all();
		return isset( $data['governorates'][ $parts[0] ]['districts'][ $parts[1] ] );
	}

	/**
	 * Get the localized district label from a composite key.
	 *
	 * @param string      $composite_key       Composite key.
	 * @param bool        $include_governorate Include governorate label.
	 * @param string|null $locale              Locale override.
	 * @return string
	 */
	public function district_label( $composite_key, $include_governorate = true, $locale = null ) {
		$key   = $this->normalize_district_key( $composite_key );
		$parts = $this->split_key( $key );

		if ( 2 !== count( $parts ) || ! $this->is_valid_district( $key ) ) {
			return '';
		}

		$data              = $this->all();
		$governorate_name = $this->localized_name( $data['governorates'][ $parts[0] ]['name'], $locale );
		$district_name    = $this->localized_name( $data['governorates'][ $parts[0] ]['districts'][ $parts[1] ], $locale );

		return $include_governorate
			? sprintf( '%1$s — %2$s', $governorate_name, $district_name )
			: $district_name;
	}

	/**
	 * Extract a governorate slug from a district key.
	 *
	 * @param string $composite_key Composite key.
	 * @return string
	 */
	public function governorate_from_district( $composite_key ) {
		$parts = $this->split_key( $this->normalize_district_key( $composite_key ) );
		return 2 === count( $parts ) ? $parts[0] : '';
	}

	/**
	 * Build a district key.
	 *
	 * @param string $governorate Governorate slug.
	 * @param string $district    District slug.
	 * @return string
	 */
	public function compose_key( $governorate, $district ) {
		return sanitize_key( $governorate ) . ':' . sanitize_key( $district );
	}

	/**
	 * Split a district key.
	 *
	 * @param string $key Composite key.
	 * @return string[]
	 */
	private function split_key( $key ) {
		$key = strtolower( trim( (string) $key ) );
		return false === strpos( $key, ':' ) ? array() : explode( ':', $key, 2 );
	}

	/**
	 * Choose the localized label.
	 *
	 * @param array<string,string> $names  Localized names.
	 * @param string|null          $locale Locale override.
	 * @return string
	 */
	private function localized_name( array $names, $locale = null ) {
		$locale = $locale ? $locale : determine_locale();
		$key    = 0 === strpos( strtolower( $locale ), 'ar' ) ? 'ar' : 'en';
		return isset( $names[ $key ] ) ? $names[ $key ] : reset( $names );
	}
}
