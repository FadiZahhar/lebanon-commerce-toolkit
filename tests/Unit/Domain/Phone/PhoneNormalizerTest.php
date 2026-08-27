<?php
/**
 * Phone normalizer tests.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Tests\Unit\Domain\Phone;

use PHPUnit\Framework\TestCase;
use ProSolutions\LebanonCommerceToolkit\Domain\Phone\PhoneNormalizer;

final class PhoneNormalizerTest extends TestCase {
	/**
	 * @dataProvider normalization_cases
	 *
	 * @param string $expected Expected output.
	 * @param string $input    Input.
	 */
	public function test_normalization( $expected, $input ): void {
		$normalizer = new PhoneNormalizer();
		self::assertSame( $expected, $normalizer->normalize( $input ) );
	}

	public function normalization_cases(): array {
		return array(
			'local mobile'        => array( '+9613123456', '03 123 456' ),
			'local eight digits'  => array( '+96171234567', '71-234-567' ),
			'international 00961' => array( '+96171234567', '00961 71 234 567' ),
			'arabic digits'       => array( '+9613123456', '٠٣ ١٢٣ ٤٥٦' ),
			'foreign explicit'    => array( '+33123456789', '+33 1 23 45 67 89' ),
		);
	}

	public function test_structural_validation(): void {
		$normalizer = new PhoneNormalizer();
		self::assertTrue( $normalizer->is_valid_lebanon( '+961 3 123 456' ) );
		self::assertFalse( $normalizer->is_valid_lebanon( '+961 123' ) );
	}
}
