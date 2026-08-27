<?php
/**
 * Shipping rate parser tests.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Tests\Unit\Domain\Shipping;

use PHPUnit\Framework\TestCase;
use ProSolutions\LebanonCommerceToolkit\Domain\Shipping\ShippingRateTable;

final class ShippingRateTableTest extends TestCase {
	public function test_resolution_order_is_district_governorate_global_method(): void {
		$table = new ShippingRateTable();
		$rates = $table->parse( "mount-lebanon:metn=4\n@keserwan-jbeil=5\n*=7" );

		self::assertSame( 4.0, $table->resolve( $rates, 'mount-lebanon:metn', 'mount-lebanon', 9.0 ) );
		self::assertSame( 5.0, $table->resolve( $rates, 'keserwan-jbeil:keserwan', 'keserwan-jbeil', 9.0 ) );
		self::assertSame( 7.0, $table->resolve( $rates, '', 'south', 9.0 ) );
		self::assertSame( 9.0, $table->resolve( array(), '', 'south', 9.0 ) );
	}

	public function test_invalid_and_negative_rules_are_ignored(): void {
		$table = new ShippingRateTable();
		$rates = $table->parse( "bad rule\nmount-lebanon:metn=-1\nbeirut:beirut=3" );

		self::assertSame( array( 'beirut:beirut' => 3.0 ), $rates );
	}
}
