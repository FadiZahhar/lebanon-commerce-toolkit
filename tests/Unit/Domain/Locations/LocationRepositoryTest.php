<?php
/**
 * Location repository tests.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Tests\Unit\Domain\Locations;

use PHPUnit\Framework\TestCase;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;

final class LocationRepositoryTest extends TestCase {
	/**
	 * Repository under test.
	 *
	 * @var LocationRepository
	 */
	private $repository;

	protected function setUp(): void {
		$this->repository = new LocationRepository( dirname( __DIR__, 5 ) . '/data/lebanon-locations.php' );
	}

	public function test_dataset_contains_nine_governorates_and_twenty_six_districts(): void {
		self::assertCount( 9, $this->repository->governorate_options( 'en' ) );
		self::assertCount( 26, $this->repository->flattened_district_options( 'en' ) );
	}

	public function test_composite_district_keys_are_validated(): void {
		self::assertTrue( $this->repository->is_valid_district( 'mount-lebanon:metn' ) );
		self::assertFalse( $this->repository->is_valid_district( 'mount-lebanon:not-real' ) );
		self::assertSame( 'mount-lebanon', $this->repository->governorate_from_district( 'mount-lebanon:metn' ) );
		self::assertTrue( $this->repository->is_valid_district( 'keserwan-jbeil:keserwan' ) );
		self::assertSame( 'keserwan-jbeil:keserwan', $this->repository->normalize_district_key( 'mount-lebanon:keserwan' ) );
		self::assertSame( 'keserwan-jbeil:jbeil', $this->repository->normalize_district_key( 'mount-lebanon:jbeil' ) );
	}

	public function test_every_flattened_district_is_canonical_and_references_a_governorate(): void {
		foreach ( array_keys( $this->repository->flattened_district_options( 'en' ) ) as $district_key ) {
			self::assertTrue( $this->repository->is_valid_district( $district_key ) );
			self::assertTrue(
				$this->repository->is_valid_governorate(
					$this->repository->governorate_from_district( $district_key )
				)
			);
		}
	}

	public function test_labels_are_localized(): void {
		self::assertSame( 'Mount Lebanon — Metn', $this->repository->district_label( 'mount-lebanon:metn', true, 'en' ) );
		self::assertSame( 'جبل لبنان — المتن', $this->repository->district_label( 'mount-lebanon:metn', true, 'ar' ) );
	}
}
