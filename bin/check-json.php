<?php
/**
 * Validate repository JSON files without requiring Node packages.
 */

$root  = dirname( __DIR__ );
$files = array(
	$root . '/.wp-env.json',
	$root . '/blueprint.json',
	$root . '/composer.json',
	$root . '/blocks/location-selector/block.json',
	$root . '/blocks/secondary-price/block.json',
);

$failed = false;

foreach ( $files as $file ) {
	$contents = file_get_contents( $file );
	json_decode( (string) $contents, true );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		fwrite( STDERR, basename( $file ) . ': ' . json_last_error_msg() . PHP_EOL );
		$failed = true;
	} else {
		echo 'Valid JSON: ' . str_replace( $root . '/', '', $file ) . PHP_EOL;
	}
}

exit( $failed ? 1 : 0 );
