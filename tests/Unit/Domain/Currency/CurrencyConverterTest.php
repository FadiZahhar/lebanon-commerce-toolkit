<?php
/**
 * Currency converter tests.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Tests\Unit\Domain\Currency;

use PHPUnit\Framework\TestCase;
use ProSolutions\LebanonCommerceToolkit\Domain\Currency\CurrencyConverter;

final class CurrencyConverterTest extends TestCase {
	public function test_conversion_can_round_to_merchant_increment(): void {
		$converter = new CurrencyConverter();
		self::assertSame( 2238000.0, $converter->convert( 25.0, 89500.0, 1000.0 ) );
	}

	public function test_zero_increment_preserves_converted_value(): void {
		$converter = new CurrencyConverter();
		self::assertSame( 2237500.0, $converter->convert( 25.0, 89500.0, 0.0 ) );
	}
}
