<?php
/**
 * Dependency-free smoke tests runnable with: php tests/smoke.php
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

require_once __DIR__ . '/bootstrap.php';

use ProSolutions\LebanonCommerceToolkit\Domain\Currency\CurrencyConverter;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;
use ProSolutions\LebanonCommerceToolkit\Domain\Phone\PhoneNormalizer;
use ProSolutions\LebanonCommerceToolkit\Domain\Shipping\ShippingRateTable;

/**
 * Throw when an expectation fails.
 *
 * @param bool   $condition Condition.
 * @param string $message   Failure message.
 * @return void
 */
function lct_expect( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$locations = new LocationRepository( dirname( __DIR__ ) . '/data/lebanon-locations.php' );
lct_expect( 9 === count( $locations->governorate_options( 'en' ) ), 'Expected 9 governorates.' );
$districts = $locations->flattened_district_options( 'en' );
lct_expect( 26 === count( $districts ), 'Expected 26 districts.' );
foreach ( array_keys( $districts ) as $district_key ) {
	lct_expect( $locations->is_valid_district( $district_key ), 'Every flattened district key must be canonical and valid.' );
	lct_expect( $locations->is_valid_governorate( $locations->governorate_from_district( $district_key ) ), 'Every district must reference a valid governorate.' );
}
lct_expect( $locations->is_valid_district( 'mount-lebanon:metn' ), 'Metn district key must be valid.' );
lct_expect( $locations->is_valid_district( 'keserwan-jbeil:keserwan' ), 'Keserwan district key must be valid.' );
lct_expect( 'keserwan-jbeil:keserwan' === $locations->normalize_district_key( 'mount-lebanon:keserwan' ), 'Legacy Keserwan alias must normalize.' );
lct_expect( 'keserwan-jbeil:jbeil' === $locations->normalize_district_key( 'mount-lebanon:jbeil' ), 'Legacy Jbeil alias must normalize.' );
lct_expect( 'Mount Lebanon — Metn' === $locations->district_label( 'mount-lebanon:metn', true, 'en' ), 'English district label mismatch.' );
lct_expect( 'جبل لبنان — المتن' === $locations->district_label( 'mount-lebanon:metn', true, 'ar' ), 'Arabic district label mismatch.' );

$phone = new PhoneNormalizer();
lct_expect( '+9613123456' === $phone->normalize( '03 123 456' ), 'Local mobile normalization failed.' );
lct_expect( '+96171234567' === $phone->normalize( '00961 71 234 567' ), 'International-prefix normalization failed.' );
lct_expect( '+9613123456' === $phone->normalize( '٠٣ ١٢٣ ٤٥٦' ), 'Arabic numeral normalization failed.' );
lct_expect( '+33123456789' === $phone->normalize( '+33 1 23 45 67 89' ), 'Foreign international number should be preserved.' );
lct_expect( $phone->is_valid_lebanon( '+961 3 123 456' ), 'Valid Lebanese number rejected.' );
lct_expect( ! $phone->is_valid_lebanon( '+961 123' ), 'Invalid Lebanese number accepted.' );

$currency = new CurrencyConverter();
lct_expect( 2238000.0 === $currency->convert( 25.0, 89500.0, 1000.0 ), 'Currency conversion/rounding failed.' );
lct_expect( '2,238,000 LBP' === $currency->format( 2238000.0, 'LBP', 0 ), 'Currency formatting failed.' );

$rates = new ShippingRateTable();
$parsed = $rates->parse(
	"mount-lebanon:metn=4.00\n@keserwan-jbeil=5.00\n*=7.00\ninvalid line"
);
lct_expect( 4.0 === $rates->resolve( $parsed, 'mount-lebanon:metn', 'mount-lebanon', null ), 'District rate resolution failed.' );
lct_expect( 5.0 === $rates->resolve( $parsed, 'keserwan-jbeil:keserwan', 'keserwan-jbeil', null ), 'Governorate fallback failed.' );
lct_expect( 7.0 === $rates->resolve( $parsed, '', 'south', null ), 'Global fallback failed.' );
lct_expect( 9.0 === $rates->resolve( array(), '', 'south', 9.0 ), 'Method fallback failed.' );

echo "Lebanon Commerce Toolkit smoke tests passed.\n";
